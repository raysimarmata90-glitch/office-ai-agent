/**
 * Komponen global: toast (kaca buram, muncul di atas-tengah).
 *
 * Pakai:
 *   InaaiToast.sukses('Tugas tersimpan.', { judul: 'Berhasil' });
 *   InaaiToast.info('...'); InaaiToast.peringatan('...');
 *   InaaiToast.galat('Detail error…');   // tidak menutup sendiri + tombol salin
 *   InaaiToast.tampil({ jenis, judul, teks, durasi });
 *
 * Toast jenis `galat` sengaja tidak menutup otomatis supaya pesan error sempat
 * dibaca dan disalin.
 */
(function () {
'use strict';

var IKON = {
sukses: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>',
info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>',
peringatan: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>',
galat: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg>'
};

var JUDUL = { sukses: 'Berhasil', info: 'Informasi', peringatan: 'Peringatan', galat: 'Terjadi Kesalahan' };

var IKON_SALIN = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="13" height="13" x="9" y="9" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
var IKON_CEK = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
var IKON_X = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';

var wadah = null;

function wadahnya() {
if (wadah && document.body.contains(wadah)) return wadah;
wadah = document.createElement('div');
wadah.className = 'tst-wrap';
wadah.setAttribute('role', 'status');
wadah.setAttribute('aria-live', 'polite');
document.body.appendChild(wadah);
return wadah;
}

function esc(v) {
return String(v == null ? '' : v).replace(/[&<>'"]/g, function (c) {
return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c];
});
}

function tutup(el) {
if (!el || el.dataset.tutup === '1') return;
el.dataset.tutup = '1';
el.classList.add('pergi');
setTimeout(function () { el.remove(); }, 220);
}

function tampil(opsi) {
opsi = opsi || {};
var jenis = IKON[opsi.jenis] ? opsi.jenis : 'info';
var teks = String(opsi.teks == null ? '' : opsi.teks);
var judul = opsi.judul || JUDUL[jenis];
// Error harus ditutup manual: pesannya sering panjang dan perlu disalin.
var manual = jenis === 'galat' || opsi.durasi === 0;
var durasi = manual ? 0 : (opsi.durasi || 4200);

var el = document.createElement('div');
el.className = 'tst tst-' + jenis;
el.innerHTML =
'<div class="tst-head">' +
'<span class="tst-ico">' + IKON[jenis] + '</span>' +
'<span class="tst-judul">' + esc(judul) + '</span>' +
(manual ? '<button type="button" class="tst-salin" data-salin>' + IKON_SALIN + '<span>Salin</span></button>' : '') +
'<button type="button" class="tst-x" data-tutup aria-label="Tutup notifikasi">' + IKON_X + '</button>' +
'</div>' +
(teks ? '<div class="tst-teks">' + esc(teks) + '</div>' : '');

wadahnya().appendChild(el);
requestAnimationFrame(function () { el.classList.add('masuk'); });

el.querySelector('[data-tutup]').addEventListener('click', function () { tutup(el); });

var salin = el.querySelector('[data-salin]');
if (salin) {
salin.addEventListener('click', function () {
var isi = judul + ': ' + teks;
var selesai = function () {
salin.innerHTML = IKON_CEK + '<span>Tersalin</span>';
salin.classList.add('ok');
setTimeout(function () { salin.innerHTML = IKON_SALIN + '<span>Salin</span>'; salin.classList.remove('ok'); }, 1800);
};
if (navigator.clipboard && navigator.clipboard.writeText) {
navigator.clipboard.writeText(isi).then(selesai, function () { salinCadangan(isi, selesai); });
} else {
salinCadangan(isi, selesai);
}
});
}

if (durasi > 0) {
var jam = setTimeout(function () { tutup(el); }, durasi);
el.addEventListener('mouseenter', function () { clearTimeout(jam); });
el.addEventListener('mouseleave', function () { jam = setTimeout(function () { tutup(el); }, 1600); });
}

return el;
}

/* Clipboard API butuh konteks aman; di http:// lokal pakai jalur lama. */
function salinCadangan(isi, selesai) {
var ta = document.createElement('textarea');
ta.value = isi;
ta.setAttribute('readonly', '');
ta.style.cssText = 'position:fixed;top:-1000px;opacity:0';
document.body.appendChild(ta);
ta.select();
try { document.execCommand('copy'); selesai(); } catch (e) { /* diamkan */ }
ta.remove();
}

window.InaaiToast = {
tampil: tampil,
sukses: function (t, o) { return tampil(Object.assign({ jenis: 'sukses', teks: t }, o || {})); },
info: function (t, o) { return tampil(Object.assign({ jenis: 'info', teks: t }, o || {})); },
peringatan: function (t, o) { return tampil(Object.assign({ jenis: 'peringatan', teks: t }, o || {})); },
galat: function (t, o) { return tampil(Object.assign({ jenis: 'galat', teks: t }, o || {})); }
};

/* Flash dari server: <div data-toast data-jenis="sukses" data-teks="..."> */
function dariServer() {
document.querySelectorAll('[data-toast]').forEach(function (el) {
tampil({ jenis: el.dataset.jenis, judul: el.dataset.judul || '', teks: el.dataset.teks || el.textContent.trim() });
el.remove();
});
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', dariServer);
else dariServer();
})();
