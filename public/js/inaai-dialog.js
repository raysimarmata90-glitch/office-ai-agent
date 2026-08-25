/**
 * Komponen global: popup dialog konfirmasi.
 *
 * Dua cara pakai:
 *
 * 1. Deklaratif — tempelkan atribut pada <form>, <a>, atau <button>:
 *      <form method="POST" action="..." data-confirm
 *            data-confirm-judul="Hapus Percakapan"
 *            data-confirm-teks="Percakapan dan seluruh pesannya akan dihapus permanen."
 *            data-confirm-ok="Hapus" data-confirm-jenis="bahaya">
 *
 * 2. Programatis:
 *      const lanjut = await InaaiDialog.konfirmasi({ judul, teks, ok, jenis });
 *
 * Butuh @include('partials.dialog') di layout.
 */
(function () {
'use strict';

var IKON = {
hapus: '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6M14 11v6"/></svg>',
peringatan: '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>',
tanya: '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/></svg>',
info: '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>'
};

// Ikon bawaan per jenis, bisa ditimpa lewat opsi/atribut `ikon`.
var IKON_JENIS = { bahaya: 'hapus', peringatan: 'peringatan', info: 'info' };

var akar, elIko, elJudul, elTeks, elOk, elBatal;
var tertunda = null; // { selesai: fn, pemicu: Element }

function siap() {
if (akar) return true;
akar = document.getElementById('inaaiDialog');
if (!akar) return false;
elIko = akar.querySelector('[data-dlg-ico]');
elJudul = akar.querySelector('[data-dlg-judul]');
elTeks = akar.querySelector('[data-dlg-teks]');
elOk = akar.querySelector('[data-dlg-ok]');
elBatal = akar.querySelector('[data-dlg-batal]');

elOk.addEventListener('click', function () { tutup(true); });
elBatal.addEventListener('click', function () { tutup(false); });
akar.addEventListener('click', function (e) { if (e.target === akar) tutup(false); });
document.addEventListener('keydown', function (e) {
if (!akar.classList.contains('open')) return;
if (e.key === 'Escape') { e.preventDefault(); tutup(false); }
if (e.key === 'Tab') jebakFokus(e);
});
return true;
}

/** Tab tetap berputar di dalam dialog selama terbuka. */
function jebakFokus(e) {
var bisa = akar.querySelectorAll('button:not(:disabled)');
if (!bisa.length) return;
var pertama = bisa[0], terakhir = bisa[bisa.length - 1];
if (e.shiftKey && document.activeElement === pertama) { e.preventDefault(); terakhir.focus(); }
else if (!e.shiftKey && document.activeElement === terakhir) { e.preventDefault(); pertama.focus(); }
}

function tutup(hasil) {
if (!akar) return;
akar.classList.remove('open');
document.body.style.overflow = '';
var t = tertunda;
tertunda = null;
if (t) {
if (t.pemicu && document.contains(t.pemicu)) t.pemicu.focus();
t.selesai(hasil);
}
}

function konfirmasi(opsi) {
opsi = opsi || {};
if (!siap()) return Promise.resolve(window.confirm(opsi.teks || opsi.judul || 'Lanjutkan?'));
if (tertunda) tutup(false);

var jenis = opsi.jenis || 'info';
var ikon = opsi.ikon || IKON_JENIS[jenis] || 'info';

elIko.className = 'dlg-ico ' + jenis;
elIko.innerHTML = IKON[ikon] || IKON.info;
elJudul.textContent = opsi.judul || 'Konfirmasi';
elTeks.textContent = opsi.teks || '';
elTeks.style.display = opsi.teks ? '' : 'none';
elOk.textContent = opsi.ok || 'Konfirmasi';
elBatal.textContent = opsi.batal || 'Batal';
elOk.className = 'btn ' + (jenis === 'bahaya' ? 'btn-danger' : 'btn-primary');

akar.classList.add('open');
document.body.style.overflow = 'hidden';
elBatal.focus();

return new Promise(function (selesai) {
tertunda = { selesai: selesai, pemicu: opsi.pemicu || null };
});
}

function opsiDari(el) {
var d = el.dataset;
return {
judul: d.confirmJudul || 'Konfirmasi',
teks: d.confirmTeks || '',
ok: d.confirmOk || 'Konfirmasi',
batal: d.confirmBatal || 'Batal',
jenis: d.confirmJenis || 'info',
ikon: d.confirmIkon || '',
pemicu: el
};
}

function pasang() {
// Form: tahan submit sampai user mengonfirmasi.
document.addEventListener('submit', function (e) {
var form = e.target.closest('form[data-confirm]');
if (!form || form.dataset.confirmLolos === '1') return;
e.preventDefault();
konfirmasi(opsiDari(form)).then(function (ya) {
if (!ya) return;
form.dataset.confirmLolos = '1';
if (typeof form.requestSubmit === 'function') form.requestSubmit();
else form.submit();
delete form.dataset.confirmLolos;
});
}, true);

// Tautan & tombol di luar form.
document.addEventListener('click', function (e) {
var el = e.target.closest('a[data-confirm],button[data-confirm]');
if (!el || el.closest('form[data-confirm]') || el.dataset.confirmLolos === '1') return;
e.preventDefault();
konfirmasi(opsiDari(el)).then(function (ya) {
if (!ya) return;
el.dataset.confirmLolos = '1';
el.click();
delete el.dataset.confirmLolos;
});
}, true);
}

window.InaaiDialog = { konfirmasi: konfirmasi };

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', pasang);
else pasang();
})();
