/**
 * Komponen card upload global (drag & drop + daftar file + validasi).
 * Pakai: <input type="file" name="evidence[]" multiple data-upload
 *          data-max-size="2" data-judul="..." data-sub="...">
 * data-max-size dalam MB (default 2, mengikuti upload_max_filesize server).
 */
(function () {
  var ICON_UP = '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5M12 3v12"/></svg>';
  var LEMBAR = '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>';
  function svg(isi) {
    return '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + isi + '</svg>';
  }

  /* Ikon + label per jenis file, dipilih dari ekstensinya. */
  var JENIS = {
    pdf:  { label: 'PDF',        kelas: 'pdf',   ikon: svg(LEMBAR + '<path d="M9 13h1.5a1.5 1.5 0 0 1 0 3H9v-3ZM9 19v-3"/>') },
    doc:  { label: 'Word',       kelas: 'doc',   ikon: svg(LEMBAR + '<path d="M8 13.5 9.3 19l1.7-4 1.7 4 1.3-5.5"/>') },
    xls:  { label: 'Excel',      kelas: 'xls',   ikon: svg('<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>') },
    ppt:  { label: 'PowerPoint', kelas: 'ppt',   ikon: svg('<rect width="18" height="14" x="3" y="3" rx="2"/><path d="M12 17v4M8 21h8"/>') },
    img:  { label: 'Gambar',     kelas: 'img',   ikon: svg('<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>') },
    zip:  { label: 'Arsip',      kelas: 'zip',   ikon: svg(LEMBAR + '<path d="M11 6h2M11 9h2M11 12h2M11 15h2"/>') },
    txt:  { label: 'Teks',       kelas: 'txt',   ikon: svg(LEMBAR + '<path d="M8 13h6M8 17h4"/>') },
    lain: { label: 'Berkas',     kelas: 'lain',  ikon: svg(LEMBAR) }
  };

  var PETA_EKST = {
    pdf: 'pdf',
    doc: 'doc', docx: 'doc', rtf: 'doc', odt: 'doc',
    xls: 'xls', xlsx: 'xls', csv: 'xls', ods: 'xls',
    ppt: 'ppt', pptx: 'ppt', odp: 'ppt',
    png: 'img', jpg: 'img', jpeg: 'img', webp: 'img', gif: 'img', bmp: 'img', svg: 'img', heic: 'img',
    zip: 'zip', rar: 'zip', '7z': 'zip', tar: 'zip', gz: 'zip',
    txt: 'txt', md: 'txt', log: 'txt'
  };

  function jenisFile(f) {
    var ext = (String(f.name).split('.').pop() || '').toLowerCase();
    var kunci = PETA_EKST[ext] || (/^image\//.test(f.type || '') ? 'img' : 'lain');
    var j = JENIS[kunci];
    // Ekstensi tak dikenal tetap ditampilkan apa adanya, bukan "Berkas".
    return kunci === 'lain' && ext ? { label: ext.toUpperCase(), kelas: 'lain', ikon: j.ikon } : j;
  }
  var ICON_X = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';

  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>'"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[c];
    });
  }

  function ukuran(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return (bytes / 1024 / 1024).toFixed(1) + ' MB';
  }

  function InaaiUpload(input) {
    if (input.dataset.uploadReady === '1') return;
    input.dataset.uploadReady = '1';

    var maxMb = parseFloat(input.dataset.maxSize || '2');
    var maxBytes = maxMb * 1024 * 1024;
    var terima = (input.getAttribute('accept') || '')
      .split(',').map(function (x) { return x.trim().replace(/^\./, '').toLowerCase(); })
      .filter(Boolean);
    var judul = input.dataset.judul || 'Seret file ke sini atau <b>pilih file</b>';
    var sub = input.dataset.sub ||
      (terima.length ? terima.join(', ').toUpperCase() : 'Semua format') + ' · maks ' + maxMb + ' MB per file';

    var wrap = document.createElement('div');
    wrap.className = 'i-up';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    input.style.display = 'none';

    var drop = document.createElement('div');
    drop.className = 'i-up-drop';
    drop.setAttribute('role', 'button');
    drop.setAttribute('tabindex', '0');
    drop.innerHTML = '<div class="i-up-ico">' + ICON_UP + '</div><div class="i-up-t">' + judul + '</div><div class="i-up-s">' + esc(sub) + '</div>';

    var list = document.createElement('div');
    list.className = 'i-up-list';

    wrap.appendChild(drop);
    wrap.appendChild(list);

    var files = [];

    function valid(f) {
      if (f.size > maxBytes) return 'Ukuran ' + ukuran(f.size) + ' melebihi batas ' + maxMb + ' MB';
      if (terima.length) {
        var ext = (f.name.split('.').pop() || '').toLowerCase();
        if (terima.indexOf(ext) === -1) return 'Format .' + ext + ' tidak didukung';
      }
      return '';
    }

    function sync() {
      var dt = new DataTransfer();
      files.forEach(function (f) { if (!valid(f)) dt.items.add(f); });
      input.files = dt.files;
      render();
    }

    function render() {
      list.innerHTML = files.map(function (f, i) {
        var err = valid(f);
        var j = jenisFile(f);
        return '<div class="i-up-item' + (err ? ' bad' : '') + '">' +
          '<span class="i-up-fi ' + j.kelas + '">' + j.ikon + '</span>' +
          '<span class="i-up-meta"><span class="i-up-fn" title="' + esc(f.name) + '">' + esc(f.name) + '</span>' +
          '<span class="i-up-fs">' + (err ? esc(err) : esc(j.label) + ' · ' + ukuran(f.size)) + '</span></span>' +
          '<button type="button" class="i-up-x" data-i="' + i + '" aria-label="Hapus file">' + ICON_X + '</button></div>';
      }).join('');
      list.querySelectorAll('.i-up-x').forEach(function (b) {
        b.addEventListener('click', function () {
          files.splice(parseInt(b.dataset.i, 10), 1);
          sync();
        });
      });
    }

    function tambah(daftar) {
      Array.prototype.forEach.call(daftar, function (f) {
        var kembar = files.some(function (x) { return x.name === f.name && x.size === f.size; });
        if (!kembar) files.push(f);
      });
      if (!input.multiple) files = files.slice(-1);
      sync();
    }

    drop.addEventListener('click', function () { input.click(); });
    drop.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
    });
    input.addEventListener('change', function () {
      if (input.dataset.silent === '1') { input.dataset.silent = '0'; return; }
      tambah(input.files);
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('drag'); });
    });
    drop.addEventListener('drop', function (e) {
      if (e.dataTransfer && e.dataTransfer.files) tambah(e.dataTransfer.files);
    });

    /* API kecil untuk form yang dipakai ulang (mis. modal assign/ubah tugas):
       mengosongkan pilihan saat mode form berganti, dan memeriksa berkas yang
       belum memenuhi syarat sebelum dikirim lewat fetch. */
    input.inaaiUpload = {
      kosongkan: function () { files = []; sync(); },
      salah: function () {
        return files.filter(function (f) { return valid(f); })
          .map(function (f) { return { nama: f.name, pesan: valid(f) }; });
      }
    };

    var form = input.closest('form');
    if (form) {
      form.addEventListener('submit', function (e) {
        var salah = files.filter(function (f) { return valid(f); });
        if (salah.length) {
          e.preventDefault();
          drop.scrollIntoView({ block: 'center', behavior: 'smooth' });
          alert('Ada file yang belum memenuhi syarat:\n\n' +
            salah.map(function (f) { return '• ' + f.name + ' — ' + valid(f); }).join('\n'));
        }
      });
    }
  }

  // jenisFile & ukuran diekspor agar daftar file di tempat lain (mis. drawer
  // detail tugas) memakai ikon dan format yang sama.
  window.InaaiUpload = { init: InaaiUpload, jenisFile: jenisFile, ukuran: ukuran };

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="file"][data-upload]').forEach(InaaiUpload);
  });
})();
