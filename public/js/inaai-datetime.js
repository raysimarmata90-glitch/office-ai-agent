/**
 * Komponen global: date picker, time picker, date-time picker, date range picker.
 *
 * Pakai — cukup tempel atribut pada <input> biasa:
 *   <input type="text" name="mulai"    data-datepicker      value="2026-08-25">
 *   <input type="text" name="jam"      data-timepicker      value="09:00">
 *   <input type="text" name="deadline" data-datetimepicker  value="2026-08-25 09:00">
 *   <input type="text" data-daterange data-name-start="mulai" data-name-end="selesai">
 *
 * Input asli dijadikan penampung nilai mesin (Y-m-d / H:i / "Y-m-d H:i") dan
 * disembunyikan; yang terlihat adalah kotak baca-saja berformat lokal. Atribut
 * `required` dipindahkan ke kotak yang terlihat supaya validasi browser tetap
 * bisa memfokuskan elemennya.
 *
 * Batas opsional: data-min="Y-m-d", data-max="Y-m-d", data-step="5" (menit).
 */
(function () {
'use strict';

var HARI = ['Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb', 'Mg'];
var BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
             'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function pad(n) { return String(n).padStart(2, '0'); }
function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
function lokal(d) { return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear(); }

function keTanggal(teks) {
var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(teks || '').trim());
if (!m) return null;
var d = new Date(+m[1], +m[2] - 1, +m[3]);
return isNaN(d) ? null : d;
}

function keJam(teks) {
var m = /(\d{1,2}):(\d{2})/.exec(String(teks || ''));
if (!m) return null;
var j = Math.min(23, +m[1]), i = Math.min(59, +m[2]);
return { j: j, i: i };
}

function samaHari(a, b) { return a && b && iso(a) === iso(b); }

var IKON = {
kalender: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>',
jam: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
kiri: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>',
kanan: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>'
};

var terbuka = null;

function tutupSemua(kecuali) {
document.querySelectorAll('.i-dt.open').forEach(function (el) {
if (el !== kecuali) el.classList.remove('open');
});
terbuka = kecuali && kecuali.classList.contains('open') ? kecuali : null;
}

function pasang(input) {
if (input.dataset.dtSiap === '1') return;
input.dataset.dtSiap = '1';

var jenis = input.hasAttribute('data-daterange') ? 'rentang'
: input.hasAttribute('data-datetimepicker') ? 'datetime'
: input.hasAttribute('data-timepicker') ? 'jam'
: 'tanggal';

var adaTanggal = jenis !== 'jam';
var adaJam = jenis === 'datetime' || jenis === 'jam';
var langkah = Math.max(1, parseInt(input.dataset.step || '5', 10));
var minTgl = keTanggal(input.dataset.min);
var maksTgl = keTanggal(input.dataset.max);

var akar = document.createElement('div');
akar.className = 'i-dt';
input.parentNode.insertBefore(akar, input);

// Input asli jadi penampung nilai mesin.
var wajib = input.hasAttribute('required');
input.removeAttribute('required');
input.type = 'hidden';
akar.appendChild(input);

// Rentang butuh dua penampung bernama.
var isiAwal = null, isiAkhir = null;
if (jenis === 'rentang') {
isiAwal = buatHidden(input.dataset.nameStart || 'mulai');
isiAkhir = buatHidden(input.dataset.nameEnd || 'selesai');
akar.appendChild(isiAwal);
akar.appendChild(isiAkhir);
}

function buatHidden(nama) {
var el = document.createElement('input');
el.type = 'hidden';
el.name = nama;
return el;
}

var tombol = document.createElement('input');
tombol.type = 'text';
tombol.className = 'i-dt-in';
tombol.readOnly = true;
tombol.placeholder = input.dataset.placeholder || (jenis === 'jam' ? 'Pilih jam' : 'Pilih tanggal');
if (wajib) tombol.required = true;
if (input.id) { tombol.id = input.id + 'Tampil'; }
akar.appendChild(tombol);

var ikon = document.createElement('span');
ikon.className = 'i-dt-ico';
ikon.innerHTML = adaTanggal ? IKON.kalender : IKON.jam;
akar.appendChild(ikon);

var pop = document.createElement('div');
pop.className = 'i-dt-pop';
akar.appendChild(pop);

// ---- keadaan ----
var awalNilai = String(input.value || '');
var pilih = adaTanggal ? keTanggal(awalNilai) : null;
var pilihAkhir = null;
var waktu = keJam(awalNilai) || { j: 9, i: 0 };
if (jenis === 'rentang') {
var potong = awalNilai.split(/\s*(?:s\/d|~|,)\s*/);
pilih = keTanggal(potong[0]);
pilihAkhir = keTanggal(potong[1]);
}
var lihat = (pilih || new Date());
lihat = new Date(lihat.getFullYear(), lihat.getMonth(), 1);

function diLuarBatas(d) {
if (minTgl && d < minTgl) return true;
if (maksTgl && d > maksTgl) return true;
return false;
}

function tulisNilai() {
if (jenis === 'jam') {
input.value = pilih === null && !awalNilai ? (input.value || '') : '';
input.value = pad(waktu.j) + ':' + pad(waktu.i);
tombol.value = input.value;
} else if (jenis === 'rentang') {
isiAwal.value = pilih ? iso(pilih) : '';
isiAkhir.value = pilihAkhir ? iso(pilihAkhir) : '';
input.value = (isiAwal.value && isiAkhir.value) ? isiAwal.value + ' s/d ' + isiAkhir.value : '';
tombol.value = (pilih && pilihAkhir) ? lokal(pilih) + ' – ' + lokal(pilihAkhir) : (pilih ? lokal(pilih) + ' – …' : '');
} else if (jenis === 'datetime') {
input.value = pilih ? iso(pilih) + ' ' + pad(waktu.j) + ':' + pad(waktu.i) : '';
tombol.value = pilih ? lokal(pilih) + ' ' + pad(waktu.j) + ':' + pad(waktu.i) : '';
} else {
input.value = pilih ? iso(pilih) : '';
tombol.value = pilih ? lokal(pilih) : '';
}
input.dispatchEvent(new Event('change', { bubbles: true }));
}

function gambar() {
var html = '';

if (adaTanggal) {
var tahun = lihat.getFullYear(), bulan = lihat.getMonth();
html += '<div class="i-dt-nav">' +
'<button type="button" class="i-dt-nb" data-geser="-1" aria-label="Bulan sebelumnya">' + IKON.kiri + '</button>' +
'<div class="i-dt-judul">' + BULAN[bulan] + ' ' + tahun + '</div>' +
'<button type="button" class="i-dt-nb" data-geser="1" aria-label="Bulan berikutnya">' + IKON.kanan + '</button>' +
'</div>';
html += '<div class="i-dt-dow">' + HARI.map(function (h) { return '<span>' + h + '</span>'; }).join('') + '</div>';

var pertama = new Date(tahun, bulan, 1);
var geser = (pertama.getDay() + 6) % 7; // pekan mulai Senin
var jml = new Date(tahun, bulan + 1, 0).getDate();
var hariIni = new Date();
var sel = [];
for (var i = 0; i < geser; i++) sel.push('<span class="i-dt-sel kosong"></span>');
for (var d = 1; d <= jml; d++) {
var tgl = new Date(tahun, bulan, d);
var kelas = 'i-dt-sel';
if (diLuarBatas(tgl)) kelas += ' mati';
if (samaHari(tgl, hariIni)) kelas += ' kini';
if (samaHari(tgl, pilih) || samaHari(tgl, pilihAkhir)) kelas += ' pilih';
if (jenis === 'rentang' && pilih && pilihAkhir && tgl > pilih && tgl < pilihAkhir) kelas += ' antara';
sel.push('<button type="button" class="' + kelas + '" data-tgl="' + iso(tgl) + '">' + d + '</button>');
}
html += '<div class="i-dt-grid">' + sel.join('') + '</div>';
}

if (adaJam) {
var jamOpt = '', menitOpt = '';
for (var j = 0; j < 24; j++) jamOpt += '<option value="' + j + '"' + (j === waktu.j ? ' selected' : '') + '>' + pad(j) + '</option>';
for (var m = 0; m < 60; m += langkah) menitOpt += '<option value="' + m + '"' + (m === waktu.i ? ' selected' : '') + '>' + pad(m) + '</option>';
html += '<div class="i-dt-jam">' + IKON.jam +
'<select data-jam aria-label="Jam">' + jamOpt + '</select><b>:</b>' +
'<select data-menit aria-label="Menit">' + menitOpt + '</select></div>';
}

html += '<div class="i-dt-foot">' +
'<button type="button" class="i-dt-t" data-kini>' + (adaTanggal ? 'Hari ini' : 'Sekarang') + '</button>' +
'<button type="button" class="i-dt-t" data-kosong>Kosongkan</button>' +
'<button type="button" class="i-dt-t utama" data-tutup>Selesai</button>' +
'</div>';

pop.innerHTML = html;
}

function buka() {
gambar();
tutupSemua(akar);
akar.classList.add('open');
terbuka = akar;
// Balik ke atas bila ruang di bawah kurang.
var r = akar.getBoundingClientRect();
akar.classList.toggle('up', window.innerHeight - r.bottom < 340 && r.top > 340);
}

tombol.addEventListener('click', function (e) {
e.stopPropagation();
if (akar.classList.contains('open')) { akar.classList.remove('open'); terbuka = null; }
else buka();
});

pop.addEventListener('click', function (e) {
e.stopPropagation();

var nav = e.target.closest('[data-geser]');
if (nav) { lihat = new Date(lihat.getFullYear(), lihat.getMonth() + (+nav.dataset.geser), 1); gambar(); return; }

var sel = e.target.closest('[data-tgl]');
if (sel && !sel.classList.contains('mati')) {
var t = keTanggal(sel.dataset.tgl);
if (jenis === 'rentang') {
if (!pilih || pilihAkhir || t < pilih) { pilih = t; pilihAkhir = null; }
else { pilihAkhir = t; }
} else {
pilih = t;
}
tulisNilai();
gambar();
if (jenis === 'tanggal') { akar.classList.remove('open'); terbuka = null; }
return;
}

if (e.target.closest('[data-kini]')) {
var n = new Date();
if (adaTanggal) { pilih = new Date(n.getFullYear(), n.getMonth(), n.getDate()); pilihAkhir = null; lihat = new Date(n.getFullYear(), n.getMonth(), 1); }
if (adaJam) { waktu = { j: n.getHours(), i: Math.floor(n.getMinutes() / langkah) * langkah }; }
tulisNilai(); gambar(); return;
}

if (e.target.closest('[data-kosong]')) {
pilih = null; pilihAkhir = null;
if (jenis === 'jam') { tombol.value = ''; input.value = ''; }
else tulisNilai();
if (jenis === 'jam') input.dispatchEvent(new Event('change', { bubbles: true }));
gambar(); return;
}

if (e.target.closest('[data-tutup]')) { akar.classList.remove('open'); terbuka = null; }
});

pop.addEventListener('change', function (e) {
if (e.target.matches('[data-jam]')) waktu.j = +e.target.value;
else if (e.target.matches('[data-menit]')) waktu.i = +e.target.value;
else return;
tulisNilai();
});

// Nilai awal ke tampilan.
if (jenis === 'jam') { if (keJam(awalNilai)) tulisNilai(); }
else if (pilih || pilihAkhir) tulisNilai();

// Batas dinamis (mis. selesai tidak boleh sebelum mulai).
akar.aturMin = function (nilai) { minTgl = keTanggal(nilai); if (akar.classList.contains('open')) gambar(); };
akar.aturMaks = function (nilai) { maksTgl = keTanggal(nilai); if (akar.classList.contains('open')) gambar(); };

/**
 * Baca ulang nilai dari input penampung lalu perbarui tampilannya.
 * Dipakai setelah form.reset(), yang mengosongkan kotak tampilan tapi
 * mengembalikan input penampung ke nilai bawaannya.
 */
akar.segarkan = function () {
var v = String(input.value || '');
if (jenis === 'jam') { waktu = keJam(v) || waktu; tombol.value = keJam(v) ? v : ''; return; }
if (jenis === 'rentang') {
var bagi = v.split(/\s*(?:s\/d|~|,)\s*/);
pilih = keTanggal(bagi[0]); pilihAkhir = keTanggal(bagi[1]);
} else {
pilih = keTanggal(v);
if (jenis === 'datetime') waktu = keJam(v) || waktu;
}
if (pilih) lihat = new Date(pilih.getFullYear(), pilih.getMonth(), 1);
if (pilih || pilihAkhir) tulisNilai(); else tombol.value = '';
};

input.inaaiDt = akar;
}

function mulai() {
document.querySelectorAll('[data-datepicker],[data-timepicker],[data-datetimepicker],[data-daterange]').forEach(pasang);
}

document.addEventListener('click', function () { tutupSemua(null); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && terbuka) tutupSemua(null); });

window.InaaiDateTime = { pasang: pasang, mulai: mulai };

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mulai);
else mulai();
})();
