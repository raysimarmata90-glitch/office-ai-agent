/**
 * Komponen select global bergaya select2 (tanpa dependency).
 * Pakai: <select data-select data-placeholder="Pilih ...">...</select>
 * Opsi bisa diberi warna titik lewat atribut: <option data-color="#f55d14">
 */
(function () {
  var ICON_CARET = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
  var ICON_SEARCH = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
  var ICON_CHECK = '<svg class="ck" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>'"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c];
    });
  }

  function InaaiSelect(select) {
    if (select.dataset.selectReady === '1') return;
    select.dataset.selectReady = '1';

    var placeholder = select.dataset.placeholder || 'Pilih...';
    var wrap = document.createElement('div');
    wrap.className = 'i-sel';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.style.position = 'absolute';
    select.style.inset = '0';
    select.style.opacity = '0';
    select.style.pointerEvents = 'none';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'i-sel-btn';
    btn.innerHTML = '<span class="i-sel-dot" style="display:none"></span><span class="i-sel-val"></span><span class="i-sel-car">' + ICON_CARET + '</span>';

    var pop = document.createElement('div');
    pop.className = 'i-sel-pop';

    wrap.appendChild(btn);
    wrap.appendChild(pop);

    var dot = btn.querySelector('.i-sel-dot');
    var val = btn.querySelector('.i-sel-val');
    var searchInput = null;
    var list = null;
    var hi = -1;

    function options() {
      return Array.prototype.slice.call(select.options);
    }

    function syncButton() {
      var o = select.options[select.selectedIndex];
      var text = o ? o.textContent.trim() : '';
      var kosong = !o || o.value === '';
      val.textContent = kosong ? (o && text ? text : placeholder) : text;
      val.classList.toggle('ph', kosong);
      var warna = o && o.dataset ? o.dataset.color : '';
      dot.style.display = warna ? 'block' : 'none';
      if (warna) dot.style.background = warna;
      wrap.classList.toggle('disabled', select.disabled);
    }

    function buildPop() {
      var opts = options();
      var perluCari = opts.length > 6;
      pop.innerHTML =
        (perluCari
          ? '<div class="i-sel-search">' + ICON_SEARCH + '<input type="text" placeholder="Cari..." aria-label="Cari pilihan"></div>'
          : '') +
        '<div class="i-sel-list"></div>';
      searchInput = pop.querySelector('input');
      list = pop.querySelector('.i-sel-list');
      renderList('');
      if (searchInput) {
        searchInput.addEventListener('input', function () { renderList(searchInput.value); });
        searchInput.addEventListener('keydown', navKeys);
      }
    }

    function renderList(q) {
      var needle = q.trim().toLowerCase();
      var html = '';
      var cocok = 0;
      options().forEach(function (o, i) {
        var teks = o.textContent.trim();
        if (needle && teks.toLowerCase().indexOf(needle) === -1) return;
        cocok++;
        html +=
          '<div class="i-sel-opt' + (i === select.selectedIndex ? ' sel' : '') + '" data-i="' + i + '">' +
          (o.dataset && o.dataset.color ? '<span class="i-sel-dot" style="background:' + esc(o.dataset.color) + '"></span>' : '') +
          '<span>' + esc(teks) + '</span>' + ICON_CHECK + '</div>';
      });
      list.innerHTML = cocok ? html : '<div class="i-sel-none">Tidak ada hasil.</div>';
      hi = -1;
      list.querySelectorAll('.i-sel-opt').forEach(function (el) {
        el.addEventListener('click', function () { pilih(parseInt(el.dataset.i, 10)); });
        el.addEventListener('mousemove', function () {
          list.querySelectorAll('.i-sel-opt').forEach(function (o) { o.classList.remove('hi'); });
          el.classList.add('hi');
          hi = Array.prototype.indexOf.call(list.querySelectorAll('.i-sel-opt'), el);
        });
      });
    }

    function pilih(i) {
      select.selectedIndex = i;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      syncButton();
      tutup();
      btn.focus();
    }

    /** Ada induk yang memotong isinya (mis. badan modal yang bergulir)? */
    function indukMemotong(el) {
      for (var n = el.parentElement; n && n !== document.body; n = n.parentElement) {
        var g = getComputedStyle(n);
        if (/(auto|scroll|hidden)/.test(g.overflowY + g.overflowX)) return n;
      }
      return null;
    }

    /**
     * Di dalam wadah yang bergulir, popup absolut akan terpotong. Karena itu
     * popup dilepas ke koordinat layar (position:fixed) saat kasus itu terjadi.
     */
    function taruhPopup() {
      var r = btn.getBoundingClientRect();
      var keAtas = r.bottom + 268 > window.innerHeight && r.top > 288;
      wrap.classList.toggle('up', keAtas);

      if (!indukMemotong(wrap)) { pop.style.cssText = ''; return; }

      pop.style.position = 'fixed';
      // Lebar mengikuti isi (min selebar tombol), lalu dijepit agar tetap di layar.
      pop.style.width = 'max-content';
      pop.style.minWidth = r.width + 'px';
      pop.style.maxWidth = Math.min(420, window.innerWidth - 24) + 'px';
      pop.style.right = 'auto';
      var lebar = pop.getBoundingClientRect().width;
      pop.style.left = Math.max(8, Math.min(r.left, window.innerWidth - lebar - 8)) + 'px';
      if (keAtas) {
        pop.style.top = 'auto';
        pop.style.bottom = (window.innerHeight - r.top + 5) + 'px';
      } else {
        pop.style.bottom = 'auto';
        pop.style.top = (r.bottom + 5) + 'px';
      }
    }

    function buka() {
      if (select.disabled) return;
      document.querySelectorAll('.i-sel.open').forEach(function (o) { o.classList.remove('open', 'up'); });
      buildPop();
      wrap.classList.add('open');
      taruhPopup();
      if (searchInput) searchInput.focus();
      window.addEventListener('scroll', taruhPopup, true);
      window.addEventListener('resize', taruhPopup);
    }

    /** Baca ulang <option> dan perbarui tampilannya (mis. setelah opsi disaring). */
    select.inaaiSel = {
      segarkan: function () { syncButton(); if (wrap.classList.contains('open')) buildPop(); }
    };

    function tutup() {
      wrap.classList.remove('open', 'up');
      pop.style.cssText = '';
      window.removeEventListener('scroll', taruhPopup, true);
      window.removeEventListener('resize', taruhPopup);
    }

    function navKeys(e) {
      var items = list.querySelectorAll('.i-sel-opt');
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (!items.length) return;
        hi = e.key === 'ArrowDown' ? Math.min(items.length - 1, hi + 1) : Math.max(0, hi - 1);
        items.forEach(function (o) { o.classList.remove('hi'); });
        items[hi].classList.add('hi');
        items[hi].scrollIntoView({ block: 'nearest' });
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (hi >= 0 && items[hi]) pilih(parseInt(items[hi].dataset.i, 10));
      } else if (e.key === 'Escape') {
        e.preventDefault();
        tutup();
        btn.focus();
      }
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      wrap.classList.contains('open') ? tutup() : buka();
    });
    btn.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); buka(); }
    });
    pop.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', tutup);
    select.addEventListener('change', syncButton);

    new MutationObserver(syncButton).observe(select, { attributes: true, attributeFilter: ['disabled'] });

    syncButton();
  }

  window.InaaiSelect = { init: InaaiSelect };

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[data-select]').forEach(InaaiSelect);
  });
})();
