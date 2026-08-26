<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Support\SesiPerangkat;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfilController extends Controller
{
    /** Data profil + daftar sesi aktif, dipakai modal profil di sidebar. */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'nama' => $user->name,
            'email' => $user->email,
            'telepon' => $user->phone,
            'bio' => $user->bio,
            'role' => $user->namaRole(),
            'departemen' => $user->department?->name,
            'inisial' => $user->inisial(),
            'foto' => $user->fotoUrl(),
            'role_id' => $user->role_id,
            'department_id' => $user->department_id,
            'boleh_atur_role' => $user->isAdmin(),
            'pilihan_role' => $this->pilihanRole($user),
            'pilihan_departemen' => self::pilihanDepartemen($user)
                ->map(fn ($d) => ['id' => $d->id, 'nama' => $d->name])->all(),
            'sesi' => $this->sesi($request),
        ]);
    }

    /** Role level tinggi hanya boleh diberikan oleh administrator. */
    public const ROLE_TERBATAS = ['admin', 'ceo', 'cto', 'coo'];

    /**
     * Departemen yang tidak bisa dipilih sendiri oleh pengguna biasa.
     * Hanya tampil bila ia memang sudah berada di dalamnya, atau bila admin.
     */
    public const DEPARTEMEN_TERBATAS = ['exec', 'ops-legal', 'ba'];

    /**
     * Departemen yang boleh dipilih seorang pengguna.
     * Departemen yang sedang dipakai selalu ikut, supaya tidak hilang dari form.
     */
    public static function pilihanDepartemen($user)
    {
        return Department::orderBy('name')
            ->get(['id', 'name', 'code'])
            ->filter(fn ($d) => $user->isAdmin()
                || ! in_array($d->code, self::DEPARTEMEN_TERBATAS, true)
                || $user->department_id === $d->id)
            ->values();
    }

    /**
     * Pilihan role untuk seorang pengguna. Non-admin tidak melihat — apalagi
     * bisa memilih — Administrator maupun jabatan C-level.
     */
    protected function pilihanRole($user): array
    {
        $q = Role::orderBy('display_name');

        if (! $user->isAdmin()) {
            $q->whereNotIn('name', self::ROLE_TERBATAS);
        }

        // Role dari departemen yang tidak boleh dipilih ikut disembunyikan,
        // supaya daftar role tidak memuat pilihan yang mustahil dicapai.
        $depBoleh = self::pilihanDepartemen($user)->pluck('id');

        return $q->get(['id', 'name', 'display_name', 'department_id'])
            ->filter(fn ($r) => $r->department_id === null || $depBoleh->contains($r->department_id))
            ->map(fn ($r) => ['id' => $r->id, 'nama' => $r->display_name ?: $r->name])
            ->values()
            ->all();
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'bio' => ['nullable', 'string', 'max:500'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ], [
            'foto.image' => 'Foto profil harus berupa gambar.',
            'foto.max' => 'Ukuran foto profil maksimal 2 MB.',
        ]);

        // Email adalah identitas login — tidak diubah lewat form ini.
        unset($validated['email']);

        // Non-admin tidak boleh menaikkan dirinya ke Administrator atau C-level.
        if (array_key_exists('role_id', $validated) && $validated['role_id'] !== null) {
            $bolehId = collect($this->pilihanRole($user))->pluck('id')->all();
            if (! in_array((int) $validated['role_id'], $bolehId, true)) {
                throw ValidationException::withMessages([
                    'role_id' => 'Anda tidak berhak memilih role tersebut.',
                ]);
            }
        } else {
            unset($validated['role_id']);
        }

        if (! array_key_exists('department_id', $validated) || $validated['department_id'] === null) {
            unset($validated['department_id']);
        } elseif (! self::pilihanDepartemen($user)->pluck('id')->contains((int) $validated['department_id'])) {
            throw ValidationException::withMessages([
                'department_id' => 'Anda tidak berhak memilih departemen tersebut.',
            ]);
        }

        if ($request->hasFile('foto')) {
            // Ganti foto lama supaya tidak menumpuk di disk.
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
            $validated['foto'] = $request->file('foto')->store('avatar', 'public');
        } else {
            unset($validated['foto']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'pesan' => 'Profil berhasil diperbarui.',
            'foto' => $user->fresh()->fotoUrl(),
            'inisial' => $user->fresh()->inisial(),
            'nama' => $user->name,
            'role' => $user->fresh()->namaRole(),
            'departemen' => $user->fresh()->department?->name,
        ]);
    }

    public function hapusFoto(Request $request)
    {
        $user = $request->user();

        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
            $user->update(['foto' => null]);
        }

        return response()->json(['success' => true, 'pesan' => 'Foto profil dihapus.']);
    }

    public function sandi(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'sandi_lama' => ['required', 'string'],
            'sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'sandi.min' => 'Password baru minimal 8 karakter.',
            'sandi.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if (! Hash::check($validated['sandi_lama'], $user->password)) {
            throw ValidationException::withMessages(['sandi_lama' => 'Password lama tidak cocok.']);
        }

        $user->update(['password' => Hash::make($validated['sandi'])]);

        // Sesi lain dibuang supaya perangkat lama tidak tetap masuk.
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return response()->json([
            'success' => true,
            'pesan' => 'Password diperbarui. Sesi di perangkat lain telah dikeluarkan.',
            'sesi' => $this->sesi($request),
        ]);
    }

    /** Keluarkan satu sesi (perangkat) selain sesi yang sedang dipakai. */
    public function keluarSesi(Request $request, string $sesi)
    {
        if ($sesi === $request->session()->getId()) {
            return response()->json(['success' => false, 'pesan' => 'Sesi ini sedang Anda pakai.'], 422);
        }

        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', $sesi)->delete();

        return response()->json([
            'success' => true,
            'pesan' => 'Perangkat berhasil dikeluarkan.',
            'sesi' => $this->sesi($request),
        ]);
    }

    /** Sesi aktif milik user, terbaru dulu. */
    protected function sesi(Request $request): array
    {
        return SesiPerangkat::daftar($request->user()->id, $request->session()->getId());
    }
}
