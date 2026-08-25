(function () {
  function parseVal(cell) {
    var raw = cell.getAttribute('data-sort');
    if (raw === null) raw = cell.textContent.trim();
    var num = parseFloat(raw.replace(/[^0-9.\-]/g, ''));
    if (raw !== '' && !isNaN(num) && /^[\s\-0-9.,%]+$/.test(raw)) return num;
    return raw.toLowerCase();
  }

  function InaaiTable(root) {
    var table = root.querySelector('table.tbl');
    if (!table) return;
    var tbody = table.querySelector('tbody');
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-row]'));
    var empty = root.querySelector('[data-empty]');
    var state = { col: null, dir: 1, page: 1, size: 10 };

    var pager = root.querySelector('[data-pager]');
    var sizeSel = pager && pager.querySelector('[data-size]');
    var info = pager && pager.querySelector('[data-info]');
    var prev = pager && pager.querySelector('[data-prev]');
    var next = pager && pager.querySelector('[data-next]');
    var jump = pager && pager.querySelector('[data-jump]');
    var jumpBtn = pager && pager.querySelector('[data-jump-go]');

    function totalPages() {
      return Math.max(1, Math.ceil(rows.length / state.size));
    }

    function render() {
      var sorted = rows.slice();
      if (state.col !== null) {
        sorted.sort(function (a, b) {
          var av = parseVal(a.children[state.col]);
          var bv = parseVal(b.children[state.col]);
          if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * state.dir;
          return String(av).localeCompare(String(bv), 'id') * state.dir;
        });
      }
      var pages = totalPages();
      if (state.page > pages) state.page = pages;
      var start = (state.page - 1) * state.size;
      var end = start + state.size;

      rows.forEach(function (r) { r.style.display = 'none'; });
      sorted.forEach(function (r, i) {
        var no = r.querySelector('[data-no]');
        if (no) no.textContent = i + 1;
        if (i >= start && i < end) r.style.display = '';
      });
      sorted.forEach(function (r) { tbody.appendChild(r); });

      if (empty) empty.style.display = rows.length ? 'none' : '';
      if (info) {
        info.textContent = rows.length
          ? (start + 1) + '–' + Math.min(end, rows.length) + ' dari ' + rows.length
          : '0 dari 0';
      }
      if (prev) prev.disabled = state.page <= 1;
      if (next) next.disabled = state.page >= pages;
      if (jump) { jump.max = pages; jump.placeholder = state.page + '/' + pages; }
    }

    table.querySelectorAll('th.sortable').forEach(function (th, idx) {
      var colIndex = Array.prototype.indexOf.call(th.parentNode.children, th);
      th.addEventListener('click', function () {
        if (state.col === colIndex) state.dir = -state.dir;
        else { state.col = colIndex; state.dir = 1; }
        table.querySelectorAll('th').forEach(function (o) { o.classList.remove('asc', 'desc'); });
        th.classList.add(state.dir === 1 ? 'asc' : 'desc');
        state.page = 1;
        render();
      });
    });

    if (sizeSel) sizeSel.addEventListener('change', function () {
      state.size = parseInt(sizeSel.value, 10) || 10;
      state.page = 1;
      render();
    });
    if (prev) prev.addEventListener('click', function () { if (state.page > 1) { state.page--; render(); } });
    if (next) next.addEventListener('click', function () { if (state.page < totalPages()) { state.page++; render(); } });
    function doJump() {
      var v = parseInt(jump.value, 10);
      if (!isNaN(v) && v >= 1 && v <= totalPages()) { state.page = v; render(); }
      jump.value = '';
    }
    if (jumpBtn) jumpBtn.addEventListener('click', doJump);
    if (jump) jump.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); doJump(); } });

    render();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-table]').forEach(InaaiTable);
  });
})();
