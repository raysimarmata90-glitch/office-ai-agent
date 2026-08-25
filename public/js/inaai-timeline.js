/**
 * Komponen global: Timeline (gantt) dengan filter rentang tampilan.
 *
 * Pakai:
 *   <div class="card" data-timeline data-tl-default="3m">
 *     <div class="card-head tl-head">
 *       <div class="card-title">...</div>
 *       <div class="tl-filter" data-tl-filter></div>
 *     </div>
 *     <div class="card-body"><div data-tl-body></div></div>
 *     <div class="card-foot" data-tl-note></div>
 *     <script type="application/json" data-tl-data>[{judul,warna,mulai,selesai,...}]</script>
 *   </div>
 *
 * Kolom dibuat proporsional terhadap lamanya periode, jadi posisi bar selalu
 * lurus dengan label di atasnya (Februari tidak dipaksa selebar Juli).
 */
(function () {
'use strict';

var RENTANG = [
{ key: 'today', label: 'Hari Ini' },
{ key: 'week', label: 'Minggu Ini' },
{ key: 'month', label: 'Bulan Ini' },
{ key: '3m', label: '3 Bulan' },
{ key: '6m', label: '6 Bulan' },
{ key: '1y', label: '1 Tahun' }
];
var HARI = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
var MAKS_BARIS = 12;
var HARI_MS = 86400000;

function pad(n) { return String(n).padStart(2, '0'); }

function keTanggal(iso) {
var p = String(iso).slice(0, 10).split('-');
return new Date(+p[0], +p[1] - 1, +p[2]);
}

function tglSingkat(d) { return pad(d.getDate()) + '/' + pad(d.getMonth() + 1); }

function kolomBulan(awal, jumlah) {
var kol = [];
for (var i = 0; i < jumlah; i++) {
var m = new Date(awal.getFullYear(), awal.getMonth() + i, 1);
var n = new Date(awal.getFullYear(), awal.getMonth() + i + 1, 1);
var label = BULAN[m.getMonth()];
if (jumlah > 6 && m.getMonth() === 0) label += " '" + String(m.getFullYear()).slice(2);
kol.push({ label: label, mulai: m, selesai: n });
}
return kol;
}

/** Jendela waktu + kolom untuk satu rentang. `selesai` bersifat eksklusif. */
function jendela(key) {
var kini = new Date();
var hariIni = new Date(kini.getFullYear(), kini.getMonth(), kini.getDate());
var awalBulan = new Date(kini.getFullYear(), kini.getMonth(), 1);
var i, kol, m, n;

if (key === 'today') {
kol = [];
for (i = 0; i < 6; i++) {
m = new Date(hariIni.getTime() + i * 4 * 3600000);
n = new Date(hariIni.getTime() + (i + 1) * 4 * 3600000);
kol.push({ label: pad(m.getHours()) + ':00', mulai: m, selesai: n });
}
return { mulai: hariIni, selesai: new Date(hariIni.getTime() + HARI_MS), kolom: kol };
}

if (key === 'week') {
var geser = (hariIni.getDay() + 6) % 7; // pekan dimulai Senin
var s = new Date(hariIni.getFullYear(), hariIni.getMonth(), hariIni.getDate() - geser);
kol = [];
for (i = 0; i < 7; i++) {
m = new Date(s.getFullYear(), s.getMonth(), s.getDate() + i);
n = new Date(s.getFullYear(), s.getMonth(), s.getDate() + i + 1);
kol.push({ label: HARI[m.getDay()] + ' ' + m.getDate(), mulai: m, selesai: n });
}
return { mulai: s, selesai: new Date(s.getFullYear(), s.getMonth(), s.getDate() + 7), kolom: kol };
}

if (key === 'month') {
var akhirBulan = new Date(kini.getFullYear(), kini.getMonth() + 1, 1);
var jml = new Date(kini.getFullYear(), kini.getMonth() + 1, 0).getDate();
kol = [];
for (var d = 1; d <= jml; d += 7) {
var habis = Math.min(d + 6, jml);
kol.push({
label: d + '–' + habis,
mulai: new Date(kini.getFullYear(), kini.getMonth(), d),
selesai: new Date(kini.getFullYear(), kini.getMonth(), habis + 1)
});
}
return { mulai: awalBulan, selesai: akhirBulan, kolom: kol };
}

var jumlah = key === '6m' ? 6 : key === '1y' ? 12 : 3;
return {
mulai: awalBulan,
selesai: new Date(awalBulan.getFullYear(), awalBulan.getMonth() + jumlah, 1),
kolom: kolomBulan(awalBulan, jumlah)
};
}

function esc(v) {
return String(v == null ? '' : v).replace(/[&<>'"]/g, function (c) {
return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c];
});
}

function pasang(root) {
var kotakFilter = root.querySelector('[data-tl-filter]');
var badan = root.querySelector('[data-tl-body]');
var catatan = root.querySelector('[data-tl-note]');
var sumber = root.querySelector('[data-tl-data]');
if (!badan || !sumber) return;

var item = [];
try { item = JSON.parse(sumber.textContent || '[]') || []; } catch (e) { item = []; }

item = item.filter(function (t) { return t && t.mulai && t.selesai; }).map(function (t) {
var mulai = keTanggal(t.mulai);
var selesai = keTanggal(t.selesai);
return {
judul: t.judul || '(tanpa judul)',
warna: t.warna || '#f55d14',
proyek: t.proyek || '',
status: t.status || '',
mulai: mulai,
// selesai inklusif: satu tugas yang mulai & selesai di hari yang sama tetap punya lebar
akhir: new Date(selesai.getTime() + HARI_MS)
};
}).sort(function (a, b) { return a.mulai - b.mulai; });

var kunciSimpan = 'inaai_tl_' + (root.id || root.dataset.tlKey || 'utama');
var aktif = root.dataset.tlDefault || '3m';
try {
var simpanan = localStorage.getItem(kunciSimpan);
if (simpanan && RENTANG.some(function (r) { return r.key === simpanan; })) aktif = simpanan;
} catch (e) { /* localStorage bisa diblokir — abaikan */ }

if (kotakFilter) {
kotakFilter.innerHTML = RENTANG.map(function (r) {
return '<button type="button" class="tl-f" data-range="' + r.key + '">' + r.label + '</button>';
}).join('');
kotakFilter.addEventListener('click', function (e) {
var b = e.target.closest('[data-range]');
if (!b) return;
aktif = b.dataset.range;
try { localStorage.setItem(kunciSimpan, aktif); } catch (err) { /* abaikan */ }
gambar();
});
}

function gambar() {
var j = jendela(aktif);
var span = j.selesai - j.mulai;
var sekarang = new Date();
var posNow = (sekarang - j.mulai) / span * 100;

if (kotakFilter) {
kotakFilter.querySelectorAll('[data-range]').forEach(function (b) {
b.classList.toggle('on', b.dataset.range === aktif);
});
}

var tampil = item.filter(function (t) { return t.akhir > j.mulai && t.mulai < j.selesai; });
var terpotong = Math.max(0, tampil.length - MAKS_BARIS);
var baris = tampil.slice(0, MAKS_BARIS);

var kepala = '<div class="gantt-head"><div></div><div class="gantt-cols" style="grid-template-columns:'
+ j.kolom.map(function (k) { return ((k.selesai - k.mulai) / span).toFixed(4) + 'fr'; }).join(' ')
+ '">' + j.kolom.map(function (k) { return '<div>' + esc(k.label) + '</div>'; }).join('') + '</div></div>';

var isi = baris.map(function (t) {
var mulai = Math.max(t.mulai, j.mulai);
var akhir = Math.min(t.akhir, j.selesai);
var kiri = (mulai - j.mulai) / span * 100;
var lebar = Math.max(1.2, (akhir - mulai) / span * 100);
if (kiri + lebar > 100) lebar = 100 - kiri;
var judul = t.judul + (t.proyek ? ' · ' + t.proyek : '')
+ ' (' + tglSingkat(t.mulai) + ' – ' + tglSingkat(new Date(t.akhir.getTime() - HARI_MS)) + ')'
+ (t.status ? ' · ' + t.status : '');
return '<div class="gantt-row">'
+ '<div class="gantt-nama" title="' + esc(judul) + '">' + esc(t.judul) + '</div>'
+ '<div class="gantt-track">'
+ (posNow >= 0 && posNow <= 100 ? '<i class="gantt-now" style="left:' + posNow.toFixed(2) + '%"></i>' : '')
+ '<div class="gantt-fill" style="left:' + kiri.toFixed(2) + '%;width:' + lebar.toFixed(2) + '%;background:'
+ esc(t.warna) + '" title="' + esc(judul) + '"></div>'
+ '</div></div>';
}).join('');

badan.innerHTML = baris.length
? '<div class="gantt-scroll">' + kepala + isi + '</div>'
: '<div class="empty">Tidak ada tugas berjadwal pada rentang ini.</div>';

if (catatan) {
var akhirTampak = new Date(j.selesai.getTime() - HARI_MS);
var teks = 'Rentang ' + tglSingkat(j.mulai) + ' – ' + tglSingkat(akhirTampak)
+ ' · menampilkan ' + baris.length + ' dari ' + item.length + ' tugas berjadwal.';
if (terpotong > 0) teks += ' ' + terpotong + ' tugas lain pada rentang ini belum ditampilkan.';
catatan.textContent = teks;
}
}

gambar();
}

function mulai() {
document.querySelectorAll('[data-timeline]').forEach(pasang);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mulai);
else mulai();
})();
