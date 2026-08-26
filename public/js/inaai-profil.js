/**
 * Modal profil pengguna: data diri + foto, ganti password, dan sesi aktif.
 * Dipicu dari tombol "Lihat Profil" di sidebar (window.InaaiProfil.buka()).
 */
(function () {
'use strict';

var modal, tabs, panels, data = null, sudahMuat = false;

/* Ikon perangkat per platform; warnanya diatur lewat kelas di CSS. */
var IKON_PERANGKAT = {
'apple': '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 12.7c0-2.4 2-3.6 2.1-3.6-1.1-1.7-2.9-1.9-3.6-1.9-1.5-.2-3 .9-3.7.9-.8 0-2-.9-3.3-.8-1.7 0-3.2 1-4.1 2.5-1.7 3-.4 7.5 1.3 9.9.8 1.2 1.8 2.5 3.1 2.5 1.2 0 1.7-.8 3.2-.8s1.9.8 3.2.8c1.3 0 2.2-1.2 3-2.4.9-1.4 1.3-2.7 1.3-2.8-.1 0-2.5-1-2.5-3.8zM14.2 5.3c.7-.8 1.1-2 1-3.2-1 0-2.2.7-2.9 1.5-.6.7-1.2 1.9-1 3.1 1.1.1 2.2-.6 2.9-1.4z"/></svg>',
'apple-mobile': '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="12" height="20" x="6" y="2" rx="2.5"/><path d="M11 18h2"/></svg>',
'windows': '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5.6 10.2 4.6v6.9H3V5.6zm0 12.8 7.2 1V11.9H3v6.5zm8.1 1.1L21 21V12H11.1v7.5zM11.1 4.5V11H21V3l-9.9 1.5z"/></svg>',
'android': '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 9.5c-.4 0-.7.3-.7.7v4.9c0 .4.3.7.7.7s.7-.3.7-.7v-4.9c0-.4-.3-.7-.7-.7zM6.4 9.5c-.4 0-.7.3-.7.7v4.9c0 .4.3.7.7.7s.7-.3.7-.7v-4.9c0-.4-.3-.7-.7-.7zM7.8 16.4c0 .5.4.9.9.9h.7v2.2c0 .4.3.7.7.7s.7-.3.7-.7v-2.2h1.5v2.2c0 .4.3.7.7.7s.7-.3.7-.7v-2.2h.7c.5 0 .9-.4.9-.9V9.8H7.8v6.6zM15.1 6.2l.8-1.2c.1-.1 0-.3-.1-.3-.1-.1-.3 0-.3.1l-.8 1.2c-.7-.3-1.4-.4-2.2-.4s-1.5.1-2.2.4l-.8-1.2c-.1-.1-.2-.2-.3-.1-.1 0-.2.2-.1.3l.8 1.2c-1.4.8-2.3 2-2.3 3.4h9.9c0-1.4-.9-2.6-2.4-3.4zm-4.6 1.9c-.3 0-.5-.2-.5-.5s.2-.5.5-.5.5.2.5.5-.2.5-.5.5zm3 0c-.3 0-.5-.2-.5-.5s.2-.5.5-.5.5.2.5.5-.2.5-.5.5z"/></svg>',
'linux': '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3.5c0-1 .7-1.8 1.7-1.8h2.6c1 0 1.7.8 1.7 1.8v4c0 1.6 3 3.6 3 8 0 3.2-2.7 5.8-6 5.8s-6-2.6-6-5.8c0-4.4 3-6.4 3-8v-4Z"/><path d="M10 7.5h.01M14 7.5h.01M10.5 11h3"/></svg>',
'lain': '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8M12 17v4"/></svg>'
};

function q(s, akar) { return (akar || modal).querySelector(s); }
function esc(v) {
return String(v == null ? '' : v).replace(/[&<>'"]/g, function (c) {
return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c];
});
}
function toast(jenis, teks, judul) {
if (window.InaaiToast) window.InaaiToast[jenis](teks, judul ? { judul: judul } : undefined);
}
function csrf() { return document.querySelector('meta[name="csrf-token"]').content; }

function tutup() {
modal.classList.remove('open');
document.body.style.overflow = '';
}

function keTab(nama) {
tabs.forEach(function (t) { t.classList.toggle('on', t.dataset.pfTab === nama); });
panels.forEach(function (p) { p.style.display = p.dataset.pfPanel === nama ? '' : 'none'; });
}

function gambarAvatar() {
var av = q('#pfAv');
av.innerHTML = data.foto
? '<img src="' + esc(data.foto) + '" alt="' + esc(data.nama) + '">'
: esc(data.inisial || '?');
var sb = document.getElementById('ufAv');
if (sb) sb.innerHTML = data.foto ? '<img src="' + esc(data.foto) + '" alt="">' : esc(data.inisial || '?');
}

/* Role dibatasi pada departemen yang dipilih; role tanpa departemen (mis. User)
   selalu tersedia karena bukan jabatan. */
var semuaRole = null;
function saringRole() {
var selDep = q('#pfInDept'), selRole = q('#pfInRole');
if (!selDep || !selRole) return;
if (!semuaRole) semuaRole = [].slice.call(selRole.options).map(function (o) {
return { v: o.value, t: o.textContent, dep: o.getAttribute('data-dep') || '' };
});
var dep = String(selDep.value || '');
var sisa = semuaRole.filter(function (o) { return o.dep === '' || o.dep === dep; });
var lama = selRole.value;
selRole.innerHTML = sisa.map(function (o) {
return '<option value="' + esc(o.v) + '" data-dep="' + esc(o.dep) + '">' + esc(o.t) + '</option>';
}).join('');
selRole.value = sisa.some(function (o) { return o.v === lama; }) ? lama : (sisa[0] ? sisa[0].v : '');
if (selRole.inaaiSel && selRole.inaaiSel.segarkan) selRole.inaaiSel.segarkan();
}

function isiForm() {
q('#pfNama').textContent = data.nama || '';
q('#pfMeta').textContent = [data.role, data.departemen].filter(Boolean).join(' · ');
q('#pfInName').value = data.nama || '';
q('#pfInEmail').value = data.email || '';
q('#pfInPhone').value = data.telepon || '';
q('#pfInBio').value = data.bio || '';
// Opsi role & departemen dirender server-side agar komponen select
// sudah membaca nilai terpilihnya sejak halaman dimuat.
gambarAvatar();
saringRole();
gambarSesi(data.sesi || []);
}

/**
 * Render daftar sesi ke sebuah kotak. `onKeluar(id)` dipanggil saat tombol
 * keluarkan perangkat ditekan — modal ubah pengguna memakai endpoint berbeda.
 */
function gambarSesiKe(kotak, daftar, onKeluar) {
if (!kotak) return;
if (!daftar.length) { kotak.innerHTML = '<div class="empty">Tidak ada sesi tercatat.</div>'; return; }
kotak.innerHTML = '<div class="pf-sesi">' + daftar.map(function (s) {
return '<div class="pf-ses' + (s.ini ? ' ini' : '') + '">' +
'<span class="pf-ses-ico ' + esc(s.jenis || 'lain') + '">' + (IKON_PERANGKAT[s.jenis] || IKON_PERANGKAT.lain) + '</span>' +
'<span class="pf-ses-m"><span class="pf-ses-t">' + esc(s.perangkat) + ' · ' + esc(s.peramban) +
(s.ini ? '<span class="pf-ses-badge">Perangkat ini</span>' : '') + '</span>' +
'<span class="pf-ses-s">IP ' + esc(s.ip) + ' · ' + esc(s.terakhir) + ' (' + esc(s.lalu) + ')</span></span>' +
(s.ini ? '' : '<button type="button" class="ico-btn" data-keluar="' + esc(s.id) + '" title="Keluarkan perangkat" aria-label="Keluarkan perangkat">' +
'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg></button>') +
'</div>';
}).join('') + '</div>';

kotak.querySelectorAll('[data-keluar]').forEach(function (b) {
b.addEventListener('click', function () { onKeluar(b.dataset.keluar); });
});
}

function gambarSesi(daftar) { gambarSesiKe(q('#pfSesi'), daftar, keluarkan); }

async function muat() {
try {
var r = await fetch('/profil', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
if (!r.ok) throw new Error(r.status);
data = await r.json();
sudahMuat = true;
isiForm();
} catch (e) {
toast('galat', 'Gagal memuat data profil. ' + e.message);
}
}

async function kirim(url, opsi, sukses) {
try {
var r = await fetch(url, Object.assign({ headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }, opsi));
var d = await r.json();
if (!r.ok) {
var pesan = d.message || 'Permintaan ditolak.';
if (d.errors) pesan = Object.values(d.errors).flat().join(' ');
toast('galat', pesan);
return null;
}
if (sukses) sukses(d);
return d;
} catch (e) {
toast('galat', 'Koneksi bermasalah: ' + e.message);
return null;
}
}

async function keluarkan(id) {
var lanjut = window.InaaiDialog
? await window.InaaiDialog.konfirmasi({
judul: 'Keluarkan Perangkat',
teks: 'Sesi di perangkat itu akan diakhiri dan perlu login ulang.',
ok: 'Iya, keluarkan perangkat', jenis: 'bahaya', ikon: 'peringatan'
})
: confirm('Keluarkan perangkat ini?');
if (!lanjut) return;

await kirim('/profil/sesi/' + encodeURIComponent(id), { method: 'DELETE' }, function (d) {
toast('sukses', d.pesan);
gambarSesi(d.sesi || []);
});
}

function pasang() {
modal = document.getElementById('profilModal');
if (!modal) return;
tabs = [].slice.call(modal.querySelectorAll('[data-pf-tab]'));
panels = [].slice.call(modal.querySelectorAll('[data-pf-panel]'));

tabs.forEach(function (t) { t.addEventListener('click', function () { keTab(t.dataset.pfTab); }); });
modal.querySelectorAll('[data-modal-close]').forEach(function (b) { b.addEventListener('click', tutup); });
modal.addEventListener('click', function (e) { if (e.target === modal) tutup(); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) tutup(); });

q('#pfInDept').addEventListener('change', saringRole);
// Jalankan sekali saat pemasangan supaya daftar role sudah tersaring
// walau data profil belum sempat dimuat.
saringRole();

q('#pfPilihFoto').addEventListener('click', function () { q('#pfFoto').click(); });
q('#pfFoto').addEventListener('change', function () {
var f = q('#pfFoto').files[0];
if (!f) return;
// Pratinjau langsung; file baru terkirim saat form disimpan.
data.foto = URL.createObjectURL(f);
gambarAvatar();
toast('info', 'Foto dipilih. Klik "Simpan Perubahan" untuk menyimpannya.');
});

q('#pfHapusFoto').addEventListener('click', async function () {
await kirim('/profil/foto', { method: 'DELETE' }, function (d) {
data.foto = null; q('#pfFoto').value = '';
gambarAvatar();
toast('sukses', d.pesan);
});
});

q('#profilForm').addEventListener('submit', async function (e) {
e.preventDefault();
var fd = new FormData(q('#profilForm'));
await kirim('/profil', { method: 'POST', body: fd }, function (d) {
data.foto = d.foto; data.inisial = d.inisial; data.nama = d.nama;
data.role = d.role || data.role; data.departemen = d.departemen || data.departemen;
gambarAvatar();
q('#pfNama').textContent = d.nama;
q('#pfMeta').textContent = [data.role, data.departemen].filter(Boolean).join(' · ');
var sub = document.querySelector('.uf-sub');
if (sub && data.role) sub.textContent = data.role;
var n = document.querySelector('.uf-nama');
if (n) n.textContent = d.nama;
toast('sukses', d.pesan);
});
});

q('#sandiForm').addEventListener('submit', async function (e) {
e.preventDefault();
var fd = new FormData(q('#sandiForm'));
await kirim('/profil/sandi', { method: 'POST', body: fd }, function (d) {
q('#sandiForm').reset();
toast('sukses', d.pesan);
if (d.sesi) gambarSesi(d.sesi);
});
});
}

window.InaaiProfil = {
/* Dipakai ulang modal ubah pengguna di halaman Tim. */
gambarSesi: gambarSesiKe,
buka: function () {
if (!modal) return;
modal.classList.add('open');
document.body.style.overflow = 'hidden';
keTab('data');
if (!sudahMuat) muat();
}
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', pasang);
else pasang();
})();
