/**
 * Seret dan lepas kartu antar kolom kanban.
 *
 * Pakai: <div class="kanban" data-kanban data-kanban-url="/tasks/__ID__/status">
 * Semua penanganan lewat delegasi di papan, jadi kartu yang datang belakangan
 * (mis. muatan bertahap kanban dashboard) ikut bisa diseret tanpa dipasangi
 * apa pun.
 *
 * Kartu dipindah lebih dulu di layar, baru statusnya dikirim; kalau server
 * menolak, kartu dikembalikan ke tempat semula. Setelah pemindahan berhasil,
 * papan memancarkan event `inaai:kanban-pindah` supaya halaman bisa
 * menyegarkan ringkasan lain (grafik, timeline) sesuai kebutuhannya.
 */
(function () {
'use strict';

var KOSONG = '<span class="kosong-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 12h6"/></svg></span><span class="kosong-t">Kosong</span>';

function csrf() {
  var m = document.querySelector('meta[name="csrf-token"]');
  return m ? m.content : '';
}

function idKartu(kartu) {
  return kartu.dataset.task || kartu.dataset.id || '';
}

function badan(kolom) {
  return kolom.querySelector('.kcol-body') || kolom;
}

/** Perbarui penghitung kolom dan tampilkan/sembunyikan penanda kosong. */
function rapikan(kolom, selisih) {
  var hitung = kolom.querySelector('.kcol-c');
  if (hitung && selisih) {
    var n = parseInt(hitung.textContent, 10);
    if (!isNaN(n)) hitung.textContent = Math.max(0, n + selisih);
  }

  var ada = kolom.querySelectorAll('.ktask').length > 0;

  // Kanban dashboard: satu elemen keadaan yang juga dipakai pemuat bertahap.
  var state = kolom.querySelector('[data-kb-state]');
  if (state) {
    if (!ada && !state.classList.contains('kosong') && !state.textContent.trim()) {
      state.className = 'kosong';
      state.innerHTML = KOSONG;
    } else if (ada && state.classList.contains('kosong')) {
      state.className = 'kb-state';
      state.textContent = '';
    }
    return;
  }

  // Kanban yang dirender penuh: penanda kosongnya elemen statis.
  var penanda = kolom.querySelector('.kosong');
  if (penanda) penanda.style.display = ada ? 'none' : '';
}

function pasang(papan) {
  if (papan.dataset.kanbanSiap === '1') return;
  papan.dataset.kanbanSiap = '1';

  var pola = papan.dataset.kanbanUrl || '/tasks/__ID__/status';
  var seret = null;

  function bersihkanSorot() {
    papan.querySelectorAll('.kcol.over').forEach(function (k) { k.classList.remove('over'); });
  }

  papan.addEventListener('dragstart', function (e) {
    var kartu = e.target.closest('.ktask');
    if (!kartu || !idKartu(kartu)) return;
    seret = kartu;
    kartu.classList.add('drag');
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', idKartu(kartu)); } catch (err) { /* Safari lama */ }
    }
  });

  papan.addEventListener('dragend', function () {
    if (seret) seret.classList.remove('drag');
    seret = null;
    bersihkanSorot();
  });

  papan.addEventListener('dragover', function (e) {
    if (!seret) return;
    var kolom = e.target.closest('.kcol');
    if (!kolom) return;
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    if (!kolom.classList.contains('over')) {
      bersihkanSorot();
      kolom.classList.add('over');
    }
  });

  papan.addEventListener('dragleave', function (e) {
    var kolom = e.target.closest('.kcol');
    if (kolom && !kolom.contains(e.relatedTarget)) kolom.classList.remove('over');
  });

  papan.addEventListener('drop', function (e) {
    var kolom = e.target.closest('.kcol');
    if (!kolom || !seret) return;
    e.preventDefault();
    bersihkanSorot();

    var kartu = seret;
    seret = null;
    kartu.classList.remove('drag');

    var asal = kartu.closest('.kcol');
    if (!asal || asal === kolom) return;

    // Simpan posisi lama supaya bisa dikembalikan kalau server menolak.
    var indukLama = kartu.parentNode;
    var sesudahLama = kartu.nextSibling;

    // Kartu disisipkan sebelum penanda kosong/keadaan, bukan di paling bawah.
    var tujuan = badan(kolom);
    var patok = tujuan.querySelector('[data-kb-state], .kosong');
    if (patok) tujuan.insertBefore(kartu, patok);
    else tujuan.appendChild(kartu);

    rapikan(asal, -1);
    rapikan(kolom, 1);

    fetch(pola.replace('__ID__', encodeURIComponent(idKartu(kartu))), {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ status: kolom.dataset.status })
    }).then(function (r) {
      if (!r.ok) throw new Error(r.status);
      return r.json().catch(function () { return {}; });
    }).then(function () {
      kartu.dataset.status = kolom.dataset.status;
      var label = kolom.querySelector('.kcol-n');
      if (window.InaaiToast) {
        window.InaaiToast.sukses('Status tugas dipindahkan ke ' + (label ? label.textContent.trim() : kolom.dataset.status) + '.');
      }
      papan.dispatchEvent(new CustomEvent('inaai:kanban-pindah', {
        bubbles: true,
        detail: { id: idKartu(kartu), dari: asal.dataset.status, ke: kolom.dataset.status }
      }));
    }).catch(function (err) {
      indukLama.insertBefore(kartu, sesudahLama);
      rapikan(asal, 1);
      rapikan(kolom, -1);
      if (window.InaaiToast) window.InaaiToast.galat('Gagal memindahkan tugas: ' + err.message);
    });
  });
}

function mulai() {
  document.querySelectorAll('[data-kanban]').forEach(pasang);
}

window.InaaiKanban = { pasang: pasang, mulai: mulai };

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mulai);
else mulai();
})();
