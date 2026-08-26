{{-- Sepotong daftar riwayat chat; dipakai server-side maupun sebagai balasan AJAX. --}}
@foreach($riwayat as $c)
@include('partials.hist-item', ['c' => $c, 'aktifId' => $aktifId ?? null])
@endforeach
