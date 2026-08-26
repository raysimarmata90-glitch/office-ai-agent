/**
 * Komponen global: Timeline (gantt) pekerjaan.
 *
 * Pakai:
 *   <div class="card" data-timeline data-tl-key="pekerjaan">
 *     <div class="card-body"><div data-tl-body></div></div>
 *     <div class="card-foot" data-tl-note></div>
 *     <script type="application/json" data-tl-data>[{...}]</script>
 *   </div>
 *
 * Rentang tampilan dikendalikan dari luar lewat window.InaaiTimeline.rentang(kunci)
 * atau atribut data-tl-default; kalau ada elemen [data-tl-filter] di dalam kartu,
 * tombol filternya digambar sendiri oleh komponen ini.
 *
 * Penyaring tambahan (mis. drilldown status) dipasang lewat
 * window.InaaiTimeline.saring(fn) — fn menerima satu item dan mengembalikan bool.
 *
 * Kolom dibuat proporsional terhadap lamanya periode, jadi posisi bar selalu
 * lurus dengan label di atasnya (Februari tidak dipaksa selebar Juli).
 */
(function () {
'use strict';

var RENTANG = [
{ key: 'today', label: 'Hari Ini' },
{ key: '30d', label: '30 Hari' },
{ key: 'week', label: 'Minggu Ini' },
{ key: 'month', label: 'Bulan Ini' },
{ key: '3m', label: '3 Bulan' },
{ key: '6m', label: '6 Bulan' },
{ key: '1y', label: '1 Tahun' }
];
var HARI = ['Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb', 'Mg'];
var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
var MAKS_BARIS = 14;
var HARI_MS = 86400000;

var instansi = [];

function pad(n) { return String(n).padStart(2, '0'); }
function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
function tglSingkat(d) { return pad(d.getDate()) + '/' + pad(d.getMonth() + 1); }

function keTanggal(teks) {
var p = String(teks || '').slice(0, 10).split('-');
if (p.length < 3) return null;
var d = new Date(+p[0], +p[1] - 1, +p[2]);
return isNaN(d) ? null : d;
}

/** Versi pucat dari warna status, dipakai sebagai bagian bar yang belum berjalan. */
function pucat(hex, alpha) {
var h = String(hex || '').replace('#', '');
if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
if (h.length !== 6) return 'rgba(120,130,150,' + alpha + ')';
return 'rgba(' + parseInt(h.slice(0, 2), 16) + ',' + parseInt(h.slice(2, 4), 16) + ','
+ parseInt(h.slice(4, 6), 16) + ',' + alpha + ')';
}

function esc(v) {
return String(v == null ? '' : v).replace(/[&<>'"]/g, function (c) {
return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c];
});
}

function kolomBulan(awal, jumlah) {
var kol = [];
for (var i = 0; i < jumlah; i++) {
var m = new Date(awal.getFullYear(), awal.getMonth() + i, 1);
var n = new Date(awal.getFullYear(), awal.getMonth() + i + 1, 1);
var label = '1 ' + BULAN[m.getMonth()];
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
label: d + ' ' + BULAN[kini.getMonth()],
mulai: new Date(kini.getFullYear(), kini.getMonth(), d),
selesai: new Date(kini.getFullYear(), kini.getMonth(), habis + 1)
});
}
return { mulai: awalBulan, selesai: akhirBulan, kolom: kol };
}

if (key === '30d') {
// 30 hari terakhir sampai hari ini, dipecah per 5 hari.
var mulai30 = new Date(hariIni.getFullYear(), hariIni.getMonth(), hariIni.getDate() - 29);
var akhir30 = new Date(hariIni.getFullYear(), hariIni.getMonth(), hariIni.getDate() + 1);
kol = [];
for (i = 0; i < 6; i++) {
m = new Date(mulai30.getFullYear(), mulai30.getMonth(), mulai30.getDate() + i * 5);
n = new Date(mulai30.getFullYear(), mulai30.getMonth(), mulai30.getDate() + (i + 1) * 5);
kol.push({ label: m.getDate() + ' ' + BULAN[m.getMonth()], mulai: m, selesai: n });
}
return { mulai: mulai30, selesai: akhir30, kolom: kol };
}

var jumlah = key === '6m' ? 6 : key === '1y' ? 12 : 3;
return {
mulai: awalBulan,
selesai: new Date(awalBulan.getFullYear(), awalBulan.getMonth() + jumlah, 1),
kolom: kolomBulan(awalBulan, jumlah)
};
}

/** Keterangan waktu sekarang, mengikuti satuan rentang yang sedang dipilih. */
function labelSekarang(key) {
var n = new Date();
if (key === 'today') return pad(n.getHours()) + ':' + pad(n.getMinutes());
if (key === '1y' || key === '6m') return BULAN[n.getMonth()] + " '" + String(n.getFullYear()).slice(2);
if (key === '3m') return BULAN[n.getMonth()];
return n.getDate() + ' ' + BULAN[n.getMonth()];
}

/* ===== Popup detail saat kursor di atas bar ===== */
var popup = null;

function sembunyiPopup() { if (popup) { popup.remove(); popup = null; } }

function tampilPopup(el) {
sembunyiPopup();
var d = el.dataset;
popup = document.createElement('div');
popup.className = 'gantt-pop';
popup.innerHTML =
'<div class="gp-h"><i style="background:' + esc(d.warna) + '"></i><span>' + esc(d.judul) + '</span></div>' +
'<div class="gp-r"><span>Proyek</span><b>' + esc(d.proyek) + '</b></div>' +
'<div class="gp-r"><span>Status</span><b>' + esc(d.status) + '</b></div>' +
'<div class="gp-r"><span>Prioritas</span><b>' + esc(d.prioritas) + '</b></div>' +
'<div class="gp-r"><span>Periode</span><b>' + esc(d.periode) + '</b></div>' +
(d.reviewer ? '<div class="gp-r"><span>Reviewer</span><b>' + esc(d.reviewer) + '</b></div>' : '') +
(d.evidence !== '' ? '<div class="gp-r"><span>Evidence</span><b>' + esc(d.evidence) + ' file</b></div>' : '');
document.body.appendChild(popup);

var r = el.getBoundingClientRect();
var p = popup.getBoundingClientRect();
popup.style.left = Math.min(Math.max(8, r.left + 16), window.innerWidth - p.width - 8) + 'px';
var atas = r.top - p.height - 9;
popup.style.top = (atas < 8 ? r.bottom + 9 : atas) + 'px';
popup.classList.add('tampil');
}

function pasang(root) {
var kotakFilter = root.querySelector('[data-tl-filter]');
var badan = root.querySelector('[data-tl-body]');
var catatan = root.querySelector('[data-tl-note]');
var sumber = root.querySelector('[data-tl-data]');
if (!badan || !sumber) return;

var mentah = [];
try { mentah = JSON.parse(sumber.textContent || '[]') || []; } catch (e) { mentah = []; }

var item = mentah.filter(function (t) { return t && t.mulai && t.selesai; }).map(function (t) {
var mulai = keTanggal(t.mulai);
var selesai = keTanggal(t.selesai);
return {
id: t.id,
judul: t.judul || '(tanpa judul)',
warna: t.warna || '#f55d14',
proyek: t.proyek || 'Tanpa Proyek',
status: t.status || '',
statusKey: t.status_key || '',
prioritas: t.prioritas || '-',
reviewer: t.reviewer || '',
evidence: t.evidence == null ? '' : t.evidence,
progres: t.progres == null ? 100 : t.progres,
mulai: mulai,
// selesai inklusif: tugas yang mulai & selesai di hari sama tetap punya lebar
akhir: new Date(selesai.getTime() + HARI_MS)
};
}).sort(function (a, b) { return a.mulai - b.mulai; });

var kunciSimpan = 'inaai_tl_' + (root.id || root.dataset.tlKey || 'utama');
var aktif = root.dataset.tlDefault || '30d';
try {
var simpanan = localStorage.getItem(kunciSimpan);
if (simpanan && RENTANG.some(function (r) { return r.key === simpanan; })) aktif = simpanan;
} catch (e) { /* localStorage bisa diblokir — abaikan */ }

var saring = function () { return true; };

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
var posNow = (new Date() - j.mulai) / span * 100;

if (kotakFilter) {
kotakFilter.querySelectorAll('[data-range]').forEach(function (b) {
b.classList.toggle('on', b.dataset.range === aktif);
});
}

var terpakai = item.filter(saring);
var tampil = terpakai.filter(function (t) { return t.akhir > j.mulai && t.mulai < j.selesai; });
var terpotong = Math.max(0, tampil.length - MAKS_BARIS);
var baris = tampil.slice(0, MAKS_BARIS);

// Garis vertikal digambar sekali sebagai satu lapisan penuh, bukan per baris,
// supaya tampak menyambung dari header sampai baris terakhir.
var akum = 0;
var garis = j.kolom.map(function (k) {
var kiri = (akum / span * 100).toFixed(3);
akum += (k.selesai - k.mulai);
return '<i class="gantt-garis" style="left:' + kiri + '%"></i>';
}).join('') + '<i class="gantt-garis" style="left:100%"></i>'
+ (posNow >= 0 && posNow <= 100 ? '<i class="gantt-now" style="left:' + posNow.toFixed(2) + '%"></i>' : '');

var kepala = '<div class="gantt-head"><div></div><div class="gantt-cols" style="grid-template-columns:'
+ j.kolom.map(function (k) { return ((k.selesai - k.mulai) / span).toFixed(4) + 'fr'; }).join(' ')
+ '">' + j.kolom.map(function (k) { return '<div>' + esc(k.label) + '</div>'; }).join('')
// Penanda hari ini di header, sewarna dengan garis aksennya.
+ (posNow >= 0 && posNow <= 100
? '<span class="gantt-now-head" style="left:' + posNow.toFixed(2) + '%">' + esc(labelSekarang(aktif)) + '</span>'
: '')
+ '</div></div>';

// Dikelompokkan per proyek, urut mengikuti tugas paling awal.
var urut = [], grup = {};
baris.forEach(function (t) {
if (!grup[t.proyek]) { grup[t.proyek] = []; urut.push(t.proyek); }
grup[t.proyek].push(t);
});

var isi = urut.map(function (nama) {
var jml = grup[nama].length;
var rows = grup[nama].map(function (t, idx) {
var mulai = Math.max(t.mulai, j.mulai);
var akhir = Math.min(t.akhir, j.selesai);
var kiri = (mulai - j.mulai) / span * 100;
var lebar = Math.max(1.2, (akhir - mulai) / span * 100);
if (kiri + lebar > 100) lebar = 100 - kiri;
var periode = tglSingkat(t.mulai) + ' – ' + tglSingkat(new Date(t.akhir.getTime() - HARI_MS));
var terakhir = idx === jml - 1;

// Nama proyek tidak diulang di bawah judul — sudah diwakili header grup.
return '<div class="gantt-row">'
+ '<div class="gantt-nama' + (terakhir ? ' akhir' : '') + '">'
+ '<span class="gantt-cabang" aria-hidden="true"></span>'
+ '<span class="gantt-judul" title="' + esc(t.judul) + '">' + esc(t.judul) + '</span></div>'
+ '<div class="gantt-track" tabindex="0"'
+ ' data-judul="' + esc(t.judul) + '" data-proyek="' + esc(t.proyek) + '"'
+ ' data-status="' + esc(t.status) + '" data-warna="' + esc(t.warna) + '"'
+ ' data-prioritas="' + esc(t.prioritas) + '" data-periode="' + esc(periode) + '"'
+ ' data-reviewer="' + esc(t.reviewer) + '" data-evidence="' + esc(t.evidence) + '">'
+ '<div class="gantt-bar" style="left:' + kiri.toFixed(2) + '%;width:' + lebar.toFixed(2) + '%;background:'
+ esc(t.warna) + '"></div>'
+ '</div></div>';
}).join('');

return '<div class="gantt-grup"><div class="gantt-grup-t"><span class="gantt-grup-nama">' + esc(nama)
+ '</span><span class="gantt-grup-n">' + jml + '</span></div>' + rows + '</div>';
}).join('');

badan.innerHTML = baris.length
? '<div class="gantt-scroll">' + kepala
+ '<div class="gantt-body"><div class="gantt-grid" aria-hidden="true"><div></div><div class="gantt-grid-in">'
+ garis + '</div></div>' + isi + '</div></div>'
: '<div class="kosong"><span class="kosong-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18M9 15h6"/></svg></span>'
+ '<span class="kosong-t">Tidak ada tugas berjadwal</span>'
+ '<span class="kosong-s">Belum ada tugas yang jatuh pada rentang waktu ini.</span></div>';

badan.querySelectorAll('.gantt-track').forEach(function (el) {
el.addEventListener('mouseenter', function () { tampilPopup(el); });
el.addEventListener('focus', function () { tampilPopup(el); });
el.addEventListener('mouseleave', sembunyiPopup);
el.addEventListener('blur', sembunyiPopup);
});

if (catatan) {
var akhirTampak = new Date(j.selesai.getTime() - HARI_MS);
var teks = 'Rentang ' + tglSingkat(j.mulai) + ' – ' + tglSingkat(akhirTampak)
+ ' · menampilkan ' + baris.length + ' dari ' + terpakai.length + ' tugas berjadwal.';
if (terpotong > 0) teks += ' ' + terpotong + ' tugas lain pada rentang ini belum ditampilkan.';
catatan.textContent = teks;
}
}

instansi.push({
rentang: function (k) { aktif = k; try { localStorage.setItem(kunciSimpan, k); } catch (e) {} gambar(); },
saring: function (fn) { saring = fn || function () { return true; }; gambar(); },
aktif: function () { return aktif; }
});

gambar();
}

function mulai() {
instansi = [];
document.querySelectorAll('[data-timeline]').forEach(pasang);
}

window.InaaiTimeline = {
mulai: mulai,
rentang: function (k) { instansi.forEach(function (i) { i.rentang(k); }); },
saring: function (fn) { instansi.forEach(function (i) { i.saring(fn); }); },
aktif: function () { return instansi[0] ? instansi[0].aktif() : '30d'; },
daftarRentang: RENTANG,
jendela: jendela
};

window.addEventListener('scroll', sembunyiPopup, true);

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mulai);
else mulai();
})();
