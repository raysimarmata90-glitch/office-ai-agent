<style>
:root{
--primary:#f55d14; --primary-soft:#fef1e9; --primary-dark:#d94c08;
--ink:#1e2130; --muted:#5b6172; --muted2:#8a90a3; --muted3:#a7acbd;
--line:#eaecf3; --line2:#e6e8f0; --line3:#eef0f6;
--bg:#f6f7fb; --card:#ffffff;
/* Palet status — lima hue berjauhan, tanpa oranye agar tidak tertukar
   dengan warna merek. Sumber kebenarannya App\Models\Task::warnaStatus(). */
--st-done:#047857;  --st-done-bg:#d6f0e5;
--st-prog:#1d4ed8;  --st-prog-bg:#dce7fd;
--st-rev:#7e22ce;   --st-rev-bg:#f1e3fd;
--st-blok:#be123c;  --st-blok-bg:#fde3ea;
--st-todo:#475569;  --st-todo-bg:#e8edf4;
--done-bg:var(--st-done-bg); --done-fg:var(--st-done);
--prog-bg:var(--st-prog-bg); --prog-fg:var(--st-prog);
--rev-bg:var(--st-rev-bg);   --rev-fg:var(--st-rev);
--blok-bg:var(--st-blok-bg); --blok-fg:var(--st-blok);
--todo-bg:var(--st-todo-bg); --todo-fg:var(--st-todo);
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--bg);color:var(--ink);font-size:14px}
a{text-decoration:none;color:inherit}
button{font-family:inherit}
.shell{display:flex;min-height:100vh}
.sidebar{width:236px;transition:width .18s ease;flex:none;background:#fff;border-right:1px solid var(--line);display:flex;flex-direction:column;padding:16px 12px;gap:3px;position:sticky;top:0;height:100vh}
.sidebar.collapsed{width:68px}
.sb-head{display:flex;align-items:center;gap:8px;padding:4px 2px 14px}
.sidebar.collapsed .sb-head{justify-content:center}
.sb-logo{width:32px;height:32px;border-radius:9px;background:var(--primary);color:#fff;display:grid;place-items:center;font-weight:800;font-size:14px;flex:none}
.sb-title{font-weight:800;font-size:15px;letter-spacing:-.01em;white-space:nowrap;margin-right:auto}
.sb-toggle{border:none;background:transparent;cursor:pointer;color:var(--muted2);width:30px;height:30px;border-radius:8px;display:grid;place-items:center;flex:none}
.sb-toggle:hover{background:var(--primary-soft);color:var(--primary)}
/* Ikon toggle mengikuti kondisi sidebar (lucide panel-left-close/open). */
.sb-toggle .ico-open{display:none}
.sidebar.collapsed .sb-toggle .ico-close{display:none}
.sidebar.collapsed .sb-toggle .ico-open{display:block}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 11px;border-radius:9px;font-size:13.5px;font-weight:500;color:var(--muted);cursor:pointer;white-space:nowrap}
.nav-item:hover{background:var(--primary-soft)}
.nav-item.active{background:var(--primary-soft);color:var(--primary);font-weight:700}
.nav-item svg{flex:none}
.sidebar.collapsed .nav-item{justify-content:center;padding:9px 0}
.sidebar.collapsed .nav-label,.sidebar.collapsed .sb-title,.sidebar.collapsed .uf-meta,.sidebar.collapsed .uf-caret{display:none}
.sb-foot{margin-top:auto;border-top:1px solid var(--line);padding-top:10px;position:relative}
.uf{display:flex;align-items:center;gap:9px;padding:8px;border-radius:10px;cursor:pointer;width:100%;border:none;background:transparent;text-align:left}
.uf:hover{background:var(--line3)}
.sidebar.collapsed .uf{justify-content:center;padding:8px 0}
.uf-av{width:30px;height:30px;border-radius:8px;background:var(--primary);color:#fff;display:grid;place-items:center;font-weight:700;font-size:11.5px;flex:none;overflow:hidden}
.uf-av img{width:100%;height:100%;object-fit:cover;display:block}
.uf-meta{min-width:0;flex:1}
.uf-nama{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.uf-sub{font-size:11.5px;color:var(--muted2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.uf-caret{color:var(--muted3);flex:none}
.uf-menu{position:absolute;bottom:calc(100% + 6px);left:6px;right:6px;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 12px 30px rgba(30,33,48,.14);padding:6px;display:none;z-index:60}
.uf-menu.open{display:block}
.uf-menu .mi{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;font-size:13px;color:var(--muted);cursor:pointer;width:100%;border:none;background:transparent;text-align:left}
.uf-menu .mi:hover{background:var(--line3)}
.uf-menu .mi.danger{color:#b23c35}
.uf-menu .mi.danger:hover{background:#fde3e1}
.uf-head{padding:9px 10px 7px;border-bottom:1px solid var(--line);margin-bottom:5px}
.uf-head .n{font-size:13px;font-weight:700}
.uf-head .e{font-size:11.5px;color:var(--muted2)}
.main{flex:1;min-width:0;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid var(--line);padding:14px 26px;display:flex;align-items:center;gap:14px;position:sticky;top:0;z-index:40}
.page-title{font-size:17px;font-weight:800;letter-spacing:-.01em}
.page-sub{font-size:12.5px;color:var(--muted2);margin-top:1px}
.content{padding:0px 0px 40px;flex:1;scrollbar-color: rgba(0, 0, 0, 0.3) transparent;}
.card{background:#fff;border:1px solid var(--line);border-radius:14px}
.card-head{padding:15px 17px 12px;border-bottom:1px solid var(--line)}
.card-title{font-size:14.5px;font-weight:700}
.card-desc{font-size:12px;color:var(--muted2);margin-top:3px;line-height:1.5}
.card-body{padding:15px 17px}
/* Kartu memakai kolom flex supaya .card-foot selalu menempel di dasarnya,
   berapa pun tinggi isinya. */
.card{display:flex;flex-direction:column}
.card > .card-body{flex:1;min-height:0}
.card-foot{margin-top:auto;padding:11px 17px;border-top:1px solid var(--line);font-size:12px;color:var(--muted2);background:#fcfcfe;border-radius:0 0 14px 14px}
.grid-kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.kpi{background:#fff;border:1px solid var(--line);border-radius:14px;padding:15px 17px}
.kpi-l{font-size:12.5px;color:var(--muted2);font-weight:500}
.kpi-v{font-size:27px;font-weight:800;letter-spacing:-.02em;margin-top:5px}
.kpi-s{font-size:11.5px;color:var(--muted3);margin-top:3px}
.btn{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--line2);background:#fff;color:var(--ink);padding:8px 13px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer}
.btn:hover{background:var(--line3)}
.btn-primary{background:var(--primary);border-color:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-dark)}
.btn-sm{padding:5px 10px;font-size:12px;border-radius:8px}
/* Sakelar tampilan (mis. Card/Table di topbar): tinggi tiap tombolnya
   disamakan dengan tombol biasa di sebelahnya. */
.sw-view{display:flex;align-items:stretch;border:1px solid var(--line2);border-radius:9px;overflow:hidden}
.sw-view .btn{height:34px;border:none;border-radius:0;padding-top:0;padding-bottom:0;font-size:13px}
.sw-view .btn + .btn{border-left:1px solid var(--line2)}
.sw-view .btn.on{background:var(--primary);color:#fff}
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;font-size:11.5px;font-weight:700}
.b-done{background:var(--done-bg);color:var(--done-fg)}
.b-prog{background:var(--prog-bg);color:var(--prog-fg)}
.b-rev{background:var(--rev-bg);color:var(--rev-fg)}
.b-blok{background:var(--blok-bg);color:var(--blok-fg)}
.b-todo{background:var(--todo-bg);color:var(--todo-fg)}
.b-risk{background:#fde3e1;color:#b23c35}
.stack{display:flex;height:9px;border-radius:999px;overflow:hidden;background:var(--line3)}
.stack i{display:block;height:100%}
.tbl-wrap{overflow-x:auto}
table.tbl{width:100%;border-collapse:collapse;font-size:13px}
table.tbl th{text-align:left;padding:10px 12px;font-size:11.5px;font-weight:700;color:var(--muted2);text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--line);white-space:nowrap;background:#fcfcfe}
table.tbl th.sortable{cursor:pointer;user-select:none}
table.tbl th.sortable:hover{color:var(--primary)}
table.tbl th .th-in{display:inline-flex;align-items:center;gap:5px}
table.tbl td{padding:10px 12px;border-bottom:1px solid var(--line3);vertical-align:middle}
table.tbl tbody tr:hover{background:#fcfcfe}
.sort-ico{opacity:.4}
th.asc .sort-ico,th.desc .sort-ico{opacity:1;color:var(--primary)}
.pager{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 14px;border-top:1px solid var(--line);font-size:12.5px;color:var(--muted)}
.pager select,.pager input{border:1px solid var(--line2);border-radius:7px;padding:5px 8px;font-size:12.5px;font-family:inherit;background:#fff}
.pager input{width:58px}
.pg-btn{border:1px solid var(--line2);background:#fff;border-radius:7px;padding:5px 10px;cursor:pointer;font-size:12.5px}
.pg-btn:hover:not(:disabled){background:var(--line3)}
.pg-btn:disabled,.pg-btn.mati{opacity:.42;cursor:not-allowed}
/* Pager server memakai <a>/<span>, jadi ikonnya perlu perataan sendiri. */
a.pg-btn,span.pg-btn{display:inline-flex;align-items:center;color:var(--ink);text-decoration:none}
.avatar{width:28px;height:28px;border-radius:8px;background:var(--primary-soft);color:var(--primary);display:grid;place-items:center;font-weight:700;font-size:11px;flex:none}
/* Avatar berfoto: gambar mengisi penuh kotaknya, mengikuti radius avatar. */
.avatar{overflow:hidden}
.avatar img{width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit}
.modal-bg{position:fixed;inset:0;background:rgba(30,33,48,.45);display:none;align-items:center;justify-content:center;z-index:100;padding:20px}
.modal-bg.open{display:flex}
.modal{background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow:auto}
.modal-head{padding:17px 19px;border-bottom:1px solid var(--line);display:flex;align-items:flex-start;gap:12px}
.modal-body{padding:17px 19px;display:grid;gap:13px}
.modal-foot{padding:13px 19px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:9px;position:sticky;bottom:0;background:#fff}
.fld label{display:block;font-size:12px;font-weight:600;color:var(--muted);margin-bottom:5px}
.fld input,.fld select,.fld textarea{width:100%;border:1px solid var(--line2);border-radius:9px;padding:9px 11px;font-size:13px;font-family:inherit;background:#fff;color:var(--ink)}
.fld input:focus,.fld select:focus,.fld textarea:focus{outline:none;border-color:var(--primary)}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:11px}
.alert{padding:11px 14px;border-radius:11px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:9px}
.alert-ok{background:var(--done-bg);color:var(--done-fg)}
.alert-err{background:var(--blok-bg);color:var(--blok-fg)}
.empty{padding:26px;text-align:center;color:var(--muted3);font-size:12.5px}
@media(max-width:1100px){.grid-kpi{grid-template-columns:repeat(2,1fr)}}
@media(max-width:720px){.sidebar{display:none}.grid-kpi{grid-template-columns:1fr}.content{padding:16px}}

/* ===== Logo ===== */
.sb-logo img{width:100%;height:100%;object-fit:contain;display:block}
.sb-logo.img{background:transparent;padding:1px}

/* ===== Sidebar: jarak menu & riwayat ===== */
.sidebar{gap:3px}
.sb-sec{font-size:11px;color:var(--muted3);font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:13px 11px 5px}
.sb-hist{overflow-y:auto;flex:1;margin:0 -4px;padding:0 4px;display:flex;flex-direction:column;gap:3px}
.hist{display:block;padding:8px 10px;border-radius:9px;cursor:pointer}
.hist:hover{background:var(--primary-soft)}
.hist.active{background:var(--primary-soft)}
.hist-r1{display:flex;align-items:center;gap:7px}
.hist-ico{color:var(--muted2);flex:none}
.hist.active .hist-ico{color:var(--primary)}
.hist-p{flex:1;min-width:0;font-size:12.5px;font-weight:700;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.hist.active .hist-p{color:var(--primary)}
.hist-t{font-size:11px;color:var(--muted3);flex:none;font-variant-numeric:tabular-nums}
.hist-r2{font-size:11.5px;color:var(--muted2);margin-top:2px;padding-left:23px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sb-empty{font-size:11.5px;color:var(--muted3);padding:6px 11px}

/* ===== Sidebar ciut: toggle di bawah logo + tombol riwayat ===== */
.sidebar.collapsed .sb-head{flex-direction:column;gap:6px;padding:4px 0 10px}
.sidebar.collapsed .sb-sec,.sidebar.collapsed .sb-hist,.sidebar.collapsed .sb-empty{display:none}
/* Tombol Chat hanya relevan saat sidebar diciutkan — di sidebar terbuka
   daftar riwayatnya sudah tampil langsung di bawah. */
.sb-hist-btn{display:none}
.sidebar.collapsed .sb-hist-btn{display:flex;justify-content:center;padding:9px 0}
.sidebar.collapsed .sb-spacer{flex:1}
.hist-fly{position:fixed;top:0;bottom:0;left:68px;width:264px;background:#fff;border-right:1px solid var(--line);box-shadow:14px 0 34px rgba(30,33,48,.10);z-index:70;padding:16px 12px;display:none;flex-direction:column}
.hist-fly.open{display:flex}
.hist-fly-h{display:flex;align-items:center;gap:8px;padding:2px 4px 11px;border-bottom:1px solid var(--line);margin-bottom:8px}
.hist-fly-t{font-size:13.5px;font-weight:800;flex:1}
.hist-fly-x{border:none;background:transparent;color:var(--muted2);cursor:pointer;width:26px;height:26px;border-radius:7px;display:grid;place-items:center}
.hist-fly-x:hover{background:var(--line3)}

/* ===== Judul card + ikon ===== */
.card-title{display:flex;align-items:center;gap:9px}
.ct-ico{width:27px;height:27px;border-radius:8px;background:var(--primary-soft);color:var(--primary);display:grid;place-items:center;flex:none}

/* ===== Komponen global: Select (select2 style) ===== */
.i-sel{position:relative}
.i-sel-btn{width:100%;display:flex;align-items:center;gap:8px;border:1px solid var(--line2);border-radius:9px;padding:9px 11px;font-size:13px;font-family:inherit;background:#fff;color:var(--ink);cursor:pointer;text-align:left}
.i-sel-btn:hover{border-color:var(--muted3)}
.i-sel.open .i-sel-btn{border-color:var(--primary)}
.i-sel.disabled .i-sel-btn{background:var(--line3);color:var(--muted3);cursor:not-allowed}
.i-sel-dot{width:9px;height:9px;border-radius:3px;flex:none}
.i-sel-val{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.i-sel-val.ph{color:var(--muted3)}
.i-sel-car{color:var(--muted2);flex:none;transition:transform .15s}
.i-sel.open .i-sel-car{transform:rotate(180deg)}
/* Lebar popup mengikuti isinya: minimal selebar tombol, maksimal 420px,
   sehingga opsi terpanjang muat tanpa perlu gulir mendatar. */
.i-sel-pop{position:absolute;z-index:320;top:calc(100% + 5px);left:0;right:auto;background:#fff;border:1px solid var(--line);border-radius:11px;box-shadow:0 14px 34px rgba(30,33,48,.16);padding:6px;display:none;width:max-content;min-width:100%;max-width:min(420px,calc(100vw - 24px));box-sizing:border-box}
.i-sel.open .i-sel-pop{display:block}
.i-sel.up .i-sel-pop{top:auto;bottom:calc(100% + 5px)}
.i-sel-search{display:flex;align-items:center;gap:7px;border:1px solid var(--line2);border-radius:8px;padding:6px 9px;margin-bottom:5px;color:var(--muted3)}
.i-sel-search input{border:none;outline:none;width:100%;font-family:inherit;font-size:12.5px;background:transparent;color:var(--ink)}
.i-sel-list{max-height:208px;overflow-y:auto;overflow-x:hidden;display:flex;flex-direction:column;gap:2px}
.i-sel-opt{display:flex;align-items:center;gap:8px;padding:8px 9px;border-radius:8px;font-size:13px;cursor:pointer;color:var(--ink);white-space:nowrap}
/* Kalau sampai menyentuh batas lebar, teks dipotong — bukan digulir. */
.i-sel-opt > span:not(.i-sel-dot):not(.ck){min-width:0;overflow:hidden;text-overflow:ellipsis}
.i-sel-opt.hi{background:var(--primary-soft);color:var(--primary)}
.i-sel-opt.sel{background:var(--primary-soft);color:var(--primary);font-weight:600}
.i-sel-opt .ck{margin-left:auto;flex:none;opacity:0}
.i-sel-opt.sel .ck{opacity:1}
.i-sel-none{padding:13px;text-align:center;font-size:12px;color:var(--muted3)}

/* ===== Komponen global: Card Upload ===== */
.i-up-drop{border:1.5px dashed var(--line2);border-radius:12px;background:#fcfcfe;padding:18px 16px;text-align:center;cursor:pointer;transition:border-color .15s,background .15s}
.i-up-drop:hover,.i-up-drop.drag{border-color:var(--primary);background:var(--primary-soft)}
.i-up-ico{width:38px;height:38px;border-radius:11px;background:var(--primary-soft);color:var(--primary);display:grid;place-items:center;margin:0 auto 9px}
.i-up-drop.drag .i-up-ico{background:#fff}
.i-up-t{font-size:13px;font-weight:600}
.i-up-t b{color:var(--primary)}
.i-up-s{font-size:11.5px;color:var(--muted3);margin-top:3px;line-height:1.5}
.i-up-list{display:grid;gap:6px;margin-top:9px}
.i-up-item{display:flex;align-items:center;gap:9px;border:1px solid var(--line2);border-radius:10px;padding:8px 10px;background:#fff}
.i-up-item.bad{border-color:#f0b8b4;background:#fef6f5}
.i-up-fi{width:32px;height:32px;border-radius:9px;background:var(--line3);color:var(--muted);display:grid;place-items:center;flex:none}
/* Warna ikon per jenis file supaya daftar lampiran mudah dipindai. */
.i-up-fi.pdf{background:#fde3ea;color:#be123c}
.i-up-fi.doc{background:#dce7fd;color:#1d4ed8}
.i-up-fi.xls{background:#d6f0e5;color:#047857}
.i-up-fi.ppt{background:#fdeadb;color:#b45309}
.i-up-fi.img{background:#f1e3fd;color:#7e22ce}
.i-up-fi.zip{background:#e8edf4;color:#475569}
.i-up-fi.txt{background:#e0f2fe;color:#0369a1}
.i-up-item.bad .i-up-fi{background:#fde3e1;color:#b23c35}
.i-up-meta{flex:1;min-width:0;display:flex;flex-direction:column}
.i-up-fn{font-size:12.5px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.i-up-fs{font-size:11px;color:var(--muted3);margin-top:1px}
.i-up-item.bad .i-up-fs{color:#b23c35}
.i-up-x{border:none;background:transparent;color:var(--muted2);cursor:pointer;padding:4px;border-radius:7px;display:grid;place-items:center;flex:none}
.i-up-x:hover{background:#fde3e1;color:#b23c35}

/* ===== Pager ikon ===== */
.pg-btn.ico{padding:0;width:30px;height:30px;display:inline-grid;place-items:center}

/* ===== Komponen global: Timeline + filter rentang ===== */
.tl-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.tl-head .card-title{flex:1;min-width:0}
.tl-filter{display:flex;gap:2px;background:var(--line3);border-radius:9px;padding:3px;max-width:100%;overflow-x:auto}
.tl-f{border:none;background:transparent;color:var(--muted);font-family:inherit;font-size:11.5px;font-weight:600;padding:5px 10px;border-radius:7px;cursor:pointer;white-space:nowrap}
.tl-f:hover{color:var(--ink)}
.tl-f.on{background:#fff;color:var(--primary);box-shadow:0 1px 3px rgba(30,33,48,.10)}
.gantt-head{display:grid;grid-template-columns:190px 1fr;gap:22px;padding-bottom:13px;margin-bottom:2px;min-width:640px}
.gantt-cols{display:grid}
.gantt-cols > div{font-size:11px;color:var(--muted3);font-weight:700;letter-spacing:.04em;text-align:left;padding-left:9px;white-space:nowrap;overflow:hidden}
/* Kelompok per proyek */
.gantt-grup{margin-bottom:6px}
.gantt-grup-t{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:700;color:var(--muted2);text-transform:uppercase;letter-spacing:.05em;padding:11px 0 7px}
.gantt-grup-n{background:var(--line3);color:var(--muted);border-radius:999px;padding:1px 7px;font-size:10.5px;letter-spacing:0;flex:none}
.gantt-row{display:grid;grid-template-columns:190px 1fr;gap:22px;align-items:center;margin-bottom:9px;min-width:640px;position:relative}
/* Kolom nama tugas tetap di tempat saat digulir mendatar, dengan tree view
   yang menyambungkan header proyek ke daftar tugasnya. */
.gantt-nama{min-width:0;position:sticky;left:0;z-index:4;background:#fff;padding:0 18px 0 26px;display:flex;align-items:center}
.gantt-judul{font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.gantt-cabang{position:absolute;left:9px;top:0;bottom:0;width:12px;pointer-events:none}
.gantt-cabang::before{content:'';position:absolute;left:0;top:0;bottom:0;border-left:1px solid var(--line2)}
.gantt-cabang::after{content:'';position:absolute;left:0;top:50%;width:10px;border-top:1px solid var(--line2)}
.gantt-nama.akhir .gantt-cabang::before{bottom:50%}
.gantt-head > div:first-child{position:sticky;left:0;z-index:4;background:#fff}
.gantt-grup-t{position:sticky;left:0;z-index:5;background:#fff;width:190px;box-sizing:border-box;padding-right:14px}
.gantt-grup-nama{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* Pemisah antar proyek */
.gantt-grup + .gantt-grup{border-top:1px solid var(--line);padding-top:2px}
/* Penanda hari ini pada header */
.gantt-now-head{position:absolute;top:-2px;transform:translateX(-50%);background:var(--primary);color:#fff;
 border-radius:999px;padding:1px 7px;font-size:9.5px;font-weight:700;letter-spacing:.02em;white-space:nowrap;z-index:6}
.gantt-cols{position:relative}
.gantt-judul{font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Tanpa track abu: yang tampak hanya garis kolom dan bar tugasnya. */
.gantt-track{position:relative;height:12px;background:transparent;border-radius:0;overflow:visible;outline:none}
/* Lapisan garis kolom: satu untuk seluruh badan chart. */
.gantt-body{position:relative;padding-top:8px}
.gantt-grid{position:absolute;inset:0;display:grid;grid-template-columns:190px 1fr;gap:22px;pointer-events:none;z-index:0;min-width:640px}
.gantt-grid-in{position:relative}
/* Bagian pucat = sisa rencana, bagian pekat = porsi yang sudah berjalan. */
.gantt-bar{position:absolute;top:0;height:12px;border-radius:999px;z-index:2}
.gantt-bar i{display:block;height:100%;border-radius:999px;min-width:0}
.gantt-track:hover .gantt-bar{filter:brightness(.96)}
.gantt-track:focus-visible .gantt-bar{box-shadow:0 0 0 2px #fff,0 0 0 4px var(--primary)}

/* Popup detail bar */
.gantt-pop{position:fixed;z-index:340;background:#fff;border:1px solid var(--line);border-radius:12px;
 box-shadow:0 16px 40px rgba(30,33,48,.18);padding:11px 13px;min-width:224px;max-width:300px;
 font-size:12.5px;opacity:0;transition:opacity .12s;pointer-events:none}
.gantt-pop.tampil{opacity:1}
.gp-h{display:flex;align-items:center;gap:8px;font-weight:700;font-size:13px;padding-bottom:8px;margin-bottom:7px;border-bottom:1px solid var(--line3)}
.gp-h i{width:9px;height:9px;border-radius:3px;flex:none}
.gp-h span{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.gp-r{display:flex;justify-content:space-between;gap:14px;padding:2px 0}
.gp-r span{color:var(--muted2)}
.gp-r b{font-weight:600;text-align:right;min-width:0}
/* Penanda hari ini: garis utuh berwarna aksen. */
.gantt-now{position:absolute;top:0;bottom:0;width:2px;background:var(--primary);border-radius:2px;z-index:1}
/* Garis pemisah kolom, lurus dengan label waktu di header. */
/* Garis kolom menyambung dari header sampai baris terakhir. */
.gantt-garis{position:absolute;top:0;bottom:0;width:1px;background:var(--line);z-index:0}

@media(max-width:720px){.tl-filter{width:100%}}

/* ===== Tombol yang dipakai sebagai menu sidebar ===== */
button.nav-item{width:100%;border:none;background:transparent;text-align:left;font-family:inherit;font-size:13.5px}

/* ===== Panel riwayat: state muat & habis ===== */
.hist-state{padding:11px 4px 4px;font-size:11.5px;color:var(--muted3);text-align:center;line-height:1.5}
.hist-state.load{display:flex;align-items:center;justify-content:center;gap:7px}
.hist-spin{width:12px;height:12px;border:2px solid var(--line2);border-top-color:var(--primary);border-radius:50%;animation:histSpin .7s linear infinite;flex:none}
@keyframes histSpin{to{transform:rotate(360deg)}}

/* ===== Komponen global: Popup dialog ===== */
.dlg-bg{position:fixed;inset:0;background:rgba(30,33,48,.45);display:none;align-items:center;justify-content:center;z-index:200;padding:20px}
.dlg-bg.open{display:flex}
.dlg{background:#fff;border-radius:16px;width:100%;max-width:430px;box-shadow:0 24px 60px rgba(30,33,48,.24);overflow:hidden}
/* Header dialog: pola sama dengan card-head — berlatar, bergaris bawah. */
.dlg-head{padding:15px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--line);background:#fcfcfe}
.dlg-ico{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;flex:none;background:var(--primary-soft);color:var(--primary)}
.dlg-ico.bahaya{background:var(--blok-bg);color:var(--blok-fg)}
.dlg-ico.peringatan{background:var(--prog-bg);color:var(--prog-fg)}
.dlg-judul{font-size:14.5px;font-weight:800;letter-spacing:-.01em;min-width:0}
/* Isi rata kiri, sejajar dengan ikon di header (18px padding header + 0). */
.dlg-body{padding:16px 18px}
.dlg-teks{font-size:13px;color:var(--muted);line-height:1.65;text-align:left}
.dlg-foot{padding:13px 18px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:9px;background:#fcfcfe}
.btn-danger{background:var(--st-blok);border-color:var(--st-blok);color:#fff}
.btn-danger:hover{background:#9f0f33}

/* ===== Komponen global: Date / Time / DateTime / Range picker ===== */
.i-dt{position:relative}
.i-dt-in{width:100%;border:1px solid var(--line2);border-radius:9px;padding:9px 34px 9px 11px;font-size:13px;font-family:inherit;background:#fff;color:var(--ink);cursor:pointer}
.i-dt-in::placeholder{color:var(--muted3)}
.i-dt-in:focus{outline:none;border-color:var(--primary)}
.i-dt.open .i-dt-in{border-color:var(--primary)}
.i-dt-ico{position:absolute;right:11px;top:50%;transform:translateY(-50%);color:var(--muted2);pointer-events:none;display:grid}
.i-dt.open .i-dt-ico{color:var(--primary)}
.i-dt-pop{position:absolute;z-index:320;top:calc(100% + 5px);left:0;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 14px 34px rgba(30,33,48,.16);padding:10px;display:none;min-width:264px}
.i-dt.open .i-dt-pop{display:block}
.i-dt.up .i-dt-pop{top:auto;bottom:calc(100% + 5px)}
.i-dt-nav{display:flex;align-items:center;gap:6px;margin-bottom:8px}
.i-dt-judul{flex:1;text-align:center;font-size:13px;font-weight:700}
.i-dt-nb{border:1px solid var(--line2);background:#fff;color:var(--muted);width:27px;height:27px;border-radius:8px;display:grid;place-items:center;cursor:pointer;flex:none}
.i-dt-nb:hover{background:var(--primary-soft);color:var(--primary);border-color:var(--primary)}
.i-dt-dow{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:3px}
.i-dt-dow span{text-align:center;font-size:10.5px;font-weight:700;color:var(--muted3);padding:3px 0}
.i-dt-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
.i-dt-sel{border:none;background:transparent;font-family:inherit;font-size:12.5px;color:var(--ink);height:31px;border-radius:8px;cursor:pointer;display:grid;place-items:center}
.i-dt-sel.kosong{cursor:default}
.i-dt-sel:hover:not(.mati):not(.kosong){background:var(--primary-soft);color:var(--primary)}
.i-dt-sel.kini{box-shadow:inset 0 0 0 1.5px var(--primary);color:var(--primary);font-weight:700}
.i-dt-sel.antara{background:var(--primary-soft);border-radius:0}
.i-dt-sel.pilih{background:var(--primary);color:#fff;font-weight:700}
.i-dt-sel.mati{color:var(--muted3);opacity:.45;cursor:not-allowed}
.i-dt-jam{display:flex;align-items:center;gap:7px;margin-top:9px;padding-top:9px;border-top:1px solid var(--line);color:var(--muted2)}
.i-dt-jam select{border:1px solid var(--line2);border-radius:8px;padding:5px 7px;font-family:inherit;font-size:12.5px;background:#fff;color:var(--ink)}
.i-dt-jam b{color:var(--muted2)}
.i-dt-foot{display:flex;gap:6px;margin-top:9px;padding-top:9px;border-top:1px solid var(--line)}
.i-dt-t{border:1px solid var(--line2);background:#fff;color:var(--muted);font-family:inherit;font-size:11.5px;font-weight:600;padding:5px 9px;border-radius:8px;cursor:pointer}
.i-dt-t:hover{background:var(--line3);color:var(--ink)}
.i-dt-t.utama{margin-left:auto;background:var(--primary);border-color:var(--primary);color:#fff}
.i-dt-t.utama:hover{background:var(--primary-dark);color:#fff}

/* ===== Kolom tengah halaman (pola sama dengan kolom pesan di chat) ===== */
.page-col{max-width:1380px;margin:0 auto;width:100%}

/* ===== KPI berikon ===== */
.kpi-h{display:flex;align-items:center;gap:9px;margin-bottom:2px}
.kpi-ico{width:28px;height:28px;border-radius:9px;display:grid;place-items:center;flex:none}
.kpi-ico.netral{background:var(--primary-soft);color:var(--primary)}
.kpi-ico.todo{background:var(--st-todo-bg);color:var(--st-todo)}
.kpi-ico.prog{background:var(--st-prog-bg);color:var(--st-prog)}
.kpi-ico.done{background:var(--st-done-bg);color:var(--st-done)}

/* ===== Tombol aksi ikon-saja ===== */
.aksi{display:flex;align-items:center;gap:5px}
.ico-btn{border:1px solid var(--line2);background:#fff;color:var(--muted);width:28px;height:28px;border-radius:8px;display:inline-grid;place-items:center;cursor:pointer;flex:none}
.ico-btn:hover{background:var(--primary-soft);color:var(--primary);border-color:var(--primary)}

/* ===== Komponen global: Drawer dari kanan ===== */
.drw-bg{position:fixed;inset:0;background:rgba(30,33,48,.42);display:none;z-index:150}
.drw-bg.open{display:block}
.drw{position:absolute;top:0;right:0;bottom:0;width:min(460px,100%);background:#fff;border-left:1px solid var(--line);box-shadow:-18px 0 44px rgba(30,33,48,.16);display:flex;flex-direction:column;animation:drwIn .22s ease-out}
@keyframes drwIn{from{transform:translateX(24px);opacity:.4}to{transform:translateX(0);opacity:1}}
.drw-head{display:flex;align-items:center;gap:11px;padding:15px 18px;border-bottom:1px solid var(--line);background:#fcfcfe}
.drw-t{flex:1;min-width:0;font-size:14.5px;font-weight:800;letter-spacing:-.01em;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.drw-body{flex:1;overflow-y:auto;padding:6px 18px 18px}
.drw-row{display:grid;grid-template-columns:104px 1fr;gap:12px;padding:11px 0;border-bottom:1px solid var(--line3);align-items:start}
.drw-k{font-size:12px;color:var(--muted2);font-weight:600}
.drw-v{font-size:13px;color:var(--ink);min-width:0;word-break:break-word}
.drw-sec{display:flex;align-items:center;gap:8px;font-size:11.5px;font-weight:700;color:var(--muted2);text-transform:uppercase;letter-spacing:.05em;padding:16px 0 9px}
.drw-n{background:var(--line3);color:var(--muted);border-radius:999px;padding:1px 8px;font-size:11px;letter-spacing:0}
.drw-ev{display:grid;gap:7px}
.drw-evi{display:flex;align-items:center;gap:9px;border:1px solid var(--line2);border-radius:10px;padding:8px 10px;background:#fff}
.drw-foot{padding:14px 18px;border-top:1px solid var(--line);display:flex;align-items:center;gap:9px;background:#fcfcfe}
.drw-foot-sela{flex:1}
@media(max-width:520px){.drw-row{grid-template-columns:1fr;gap:3px}}

/* ===== Komponen global: Toast (kaca buram, atas-tengah) ===== */
.tst-wrap{position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:300;display:flex;flex-direction:column;gap:9px;width:min(430px,calc(100vw - 28px));pointer-events:none}
.tst{pointer-events:auto;border-radius:14px;overflow:hidden;opacity:0;transform:translateY(-10px);transition:opacity .2s ease,transform .2s ease;
 background:rgba(255,255,255,.72);backdrop-filter:blur(14px) saturate(180%);-webkit-backdrop-filter:blur(14px) saturate(180%);
 border:1px solid rgba(255,255,255,.7);box-shadow:0 14px 40px rgba(30,33,48,.18)}
@supports not (backdrop-filter:blur(4px)){.tst{background:#fff}}
.tst.masuk{opacity:1;transform:translateY(0)}
.tst.pergi{opacity:0;transform:translateY(-8px)}
.tst-head{display:flex;align-items:center;gap:9px;padding:9px 11px;border-bottom:1px solid rgba(30,33,48,.09)}
.tst-ico{width:26px;height:26px;border-radius:50%;display:grid;place-items:center;flex:none}
.tst-judul{flex:1;min-width:0;font-size:12.5px;font-weight:800;letter-spacing:-.01em;color:var(--ink)}
.tst-x{border:none;background:transparent;color:var(--muted2);cursor:pointer;width:24px;height:24px;border-radius:7px;display:grid;place-items:center;flex:none}
.tst-x:hover{background:rgba(30,33,48,.08);color:var(--ink)}
.tst-salin{display:inline-flex;align-items:center;gap:5px;border:1px solid rgba(30,33,48,.14);background:rgba(255,255,255,.7);color:var(--muted);
 font-family:inherit;font-size:11px;font-weight:700;padding:3px 8px;border-radius:7px;cursor:pointer;flex:none}
.tst-salin:hover{background:#fff;color:var(--ink)}
.tst-salin.ok{color:var(--st-done);border-color:var(--st-done)}
/* Teks di atas latar buram: warna pekat, bukan abu muda. */
.tst-teks{padding:10px 12px;font-size:13px;line-height:1.6;color:#252a3b;white-space:pre-wrap;word-break:break-word;max-height:40vh;overflow-y:auto}
.tst-sukses .tst-ico{background:var(--st-done-bg);color:var(--st-done)}
.tst-info .tst-ico{background:var(--st-prog-bg);color:var(--st-prog)}
.tst-peringatan .tst-ico{background:#fdeadb;color:#b45309}
.tst-galat .tst-ico{background:var(--st-blok-bg);color:var(--st-blok)}
@media(max-width:520px){.tst-wrap{top:10px}}

/* ===== Modal profil ===== */
.pf-tabs{display:flex;gap:2px;padding:10px 19px 0;border-bottom:1px solid var(--line);background:#fcfcfe}
.pf-tab{display:inline-flex;align-items:center;gap:7px;border:none;background:transparent;color:var(--muted);font-family:inherit;font-size:12.5px;font-weight:600;padding:9px 11px;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.pf-tab:hover{color:var(--ink)}
.pf-tab.on{color:var(--primary);border-bottom-color:var(--primary)}
.pf-foto{display:flex;align-items:center;gap:14px}
.pf-av{width:64px;height:64px;border-radius:50%;background:var(--primary);color:#fff;display:grid;place-items:center;font-weight:800;font-size:20px;flex:none;overflow:hidden}
.pf-av img{width:100%;height:100%;object-fit:cover;display:block}
.pf-nama{font-size:15px;font-weight:800;letter-spacing:-.01em}
.pf-meta{font-size:12px;color:var(--muted2);margin-top:1px}
.pf-aksi{display:flex;gap:7px;margin-top:9px;flex-wrap:wrap}
.pf-sesi{display:grid;gap:8px}
.pf-ses{display:flex;align-items:center;gap:11px;border:1px solid var(--line2);border-radius:11px;padding:10px 12px}
.pf-ses.ini{border-color:var(--primary);background:var(--primary-soft)}
.pf-ses-ico{width:32px;height:32px;border-radius:9px;background:var(--line3);color:var(--muted);display:grid;place-items:center;flex:none}
.pf-ses.ini .pf-ses-ico{background:#fff;color:var(--primary)}
.pf-ses-m{flex:1;min-width:0;display:flex;flex-direction:column}
.pf-ses-t{font-size:13px;font-weight:700;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.pf-ses-badge{background:var(--primary);color:#fff;border-radius:999px;padding:1px 8px;font-size:10.5px;font-weight:700}
.pf-ses-s{font-size:11.5px;color:var(--muted2);margin-top:2px}

/* ===== Kartu kanban: aksi, prioritas, reviewer ===== */
.ktask-top{display:flex;align-items:flex-start;gap:7px}
.ktask-top .ktask-j{flex:1;min-width:0}
.ktask-tag{display:flex;align-items:center;gap:6px;margin-top:7px;flex-wrap:wrap}
.ktask-rev{margin-left:auto;display:inline-flex}
.ico-btn.xs{width:22px;height:22px;border-radius:6px;border-color:transparent;background:transparent;flex:none;opacity:0;transition:opacity .12s}
.ktask:hover .ico-btn.xs,.ico-btn.xs:focus-visible{opacity:1}
.ico-btn.xs:hover{background:var(--st-blok-bg);color:var(--st-blok);border-color:var(--st-blok)}
.avatar.xs{width:22px;height:22px;border-radius:50%;font-size:9.5px}
/* Ikon di header tabel */
.th-ico{opacity:.75;flex:none}
table.tbl th .th-in{gap:6px}

/* ===== Profil: field terkunci & ikon perangkat ===== */
.fld-kunci{display:inline-flex;align-items:center;gap:4px;background:var(--line3);color:var(--muted2);border-radius:999px;padding:1px 7px;font-size:10px;font-weight:700;margin-left:6px;text-transform:none;letter-spacing:0}
/* Kotak date picker juga readonly, tapi tetap harus terlihat bisa diklik. */
.fld input[readonly]:not(.i-dt-in),.fld input:disabled:not(.i-dt-in){background:var(--line3);color:var(--muted2);cursor:not-allowed}
/* Warna ikon mengikuti identitas resmi tiap platform. */
.pf-ses-ico.apple,.pf-ses-ico.apple-mobile{background:#f1f2f5;color:#1d1d1f}
.pf-ses-ico.windows{background:#e3f2fd;color:#0078d4}
.pf-ses-ico.android{background:#e8f5e9;color:#3ddc84}
.pf-ses-ico.linux{background:#fff4e5;color:#e95420}
.pf-ses-ico.lain{background:var(--line3);color:var(--muted)}
.pf-ses.ini .pf-ses-ico{background:#fff}

/* ===== Komposisi status: pie + bar ===== */
.komposisi{display:grid;grid-template-columns:auto 1fr;gap:26px;align-items:center}
.pie-wrap{display:grid;place-items:center}
.pie{width:168px;height:168px;border-radius:50%;display:grid;place-items:center;position:relative}
.pie-hole{width:104px;height:104px;border-radius:50%;background:#fff;display:grid;place-items:center;align-content:center;box-shadow:inset 0 0 0 1px var(--line)}
.pie-n{font-size:26px;font-weight:800;letter-spacing:-.02em;line-height:1}
.pie-l{font-size:11.5px;color:var(--muted2);margin-top:2px}
.komposisi-bar{display:grid;gap:11px;min-width:0}
.kb-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:5px}
.kb-nama{display:inline-flex;align-items:center;gap:7px;font-size:12.5px;font-weight:600;color:var(--muted)}
.kb-nilai{font-size:12.5px;font-weight:700;white-space:nowrap}
.kb-persen{color:var(--muted3);font-weight:500;font-size:11.5px}
.kb-track{height:8px;border-radius:999px;background:var(--line3);overflow:hidden}
.kb-track i{display:block;height:100%;border-radius:999px;transition:width .25s ease}
@media(max-width:760px){.komposisi{grid-template-columns:1fr;gap:18px}}

/* ===== Modal: header tetap, badan bergulir, footer menempel ===== */
.modal{display:flex;flex-direction:column;overflow:hidden}
.modal-head{flex:none;align-items:center}
/* Satu section per bagian, supaya paddingnya bisa diatur sendiri-sendiri. */
.modal-form{display:flex;flex-direction:column;flex:1;min-height:0}
.modal-body{overflow-y:auto;min-height:0;flex:1;padding:17px 19px}
.modal-foot{flex:none;padding:14px 19px;border-top:1px solid var(--line);display:flex;
 align-items:center;justify-content:flex-end;gap:9px;background:#fcfcfe;position:static}

/* ===== Tabel: potong isi panjang agar tetap satu baris ===== */
table.tbl td{white-space:nowrap}
table.tbl td .potong{display:block;max-width:var(--w,220px);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* ===== Kanban: judul 2 baris, tinggi maksimum, hover edit biru ===== */
.ktask-j{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.ktask-m{justify-content:flex-start}
.ktask-ev{display:flex;align-items:center;justify-content:space-between;gap:8px}
.ico-btn.xs[data-ubah]:hover{background:var(--st-prog-bg);color:var(--st-prog);border-color:var(--st-prog)}

/* ===== Tabel: periode di bawah judul ===== */
.td-judul{display:flex;flex-direction:column;gap:3px}
.td-periode{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--muted2);font-weight:500;white-space:nowrap}
.td-periode svg{opacity:.7;flex:none}

/* ===== Komposisi status: bisa diklik (drilldown) ===== */
.kb-row{border:1px solid transparent;border-radius:10px;padding:7px 9px;cursor:pointer;transition:background .12s,border-color .12s}
.kb-row:hover{background:var(--line3)}
.kb-row.on{background:var(--primary-soft);border-color:var(--primary)}
.kb-sub{font-size:11px;color:var(--muted3);font-weight:500}
.kb-drill{margin-top:14px;border-top:1px solid var(--line);padding-top:13px}
.kb-drill-h{display:flex;align-items:center;gap:9px;margin-bottom:10px}
.kb-drill-t{font-size:12.5px;font-weight:700;flex:1;min-width:0}
.kb-proy{border:1px solid var(--line2);border-radius:11px;padding:10px 12px;margin-bottom:8px}
.kb-proy-t{display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:700;margin-bottom:6px}
.kb-proy-n{margin-left:auto;background:var(--line3);color:var(--muted);border-radius:999px;padding:1px 8px;font-size:11px}
.kb-task{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);padding:3px 0}
.kb-task i{width:5px;height:5px;border-radius:50%;background:var(--muted3);flex:none}

/* ===== Filter rentang di awal halaman ===== */
.page-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;margin-top:14px}
.page-bar-l{font-size:12.5px;font-weight:700;color:var(--muted2);display:inline-flex;align-items:center;gap:7px}
.tl-f{display:inline-flex;align-items:center;gap:6px}

/* ===== Placeholder data kosong ===== */
/* Placeholder kosong selalu di tengah pembungkusnya, mendatar maupun tegak. */
.kosong{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;padding:30px 18px;text-align:center;min-height:180px;margin:auto}
.kosong-ico{width:46px;height:46px;border-radius:14px;background:var(--line3);color:var(--muted3);display:grid;place-items:center;margin-bottom:7px}
.kosong-t{font-size:13px;font-weight:700;color:var(--muted)}
.kosong-s{font-size:12px;color:var(--muted3);line-height:1.5;max-width:320px}
/* Kolom kanban kosong: kotak putus-putus agar kontras dengan latar kolom. */
.kcol .kosong{padding:18px 8px;min-height:118px;height:100%;flex:0 0 auto;width:100%;border:1.5px dashed var(--line2);border-radius:11px;}
.kcol .kosong-ico{background:var(--line3);color:var(--muted3)}
.kcol .kosong-ico{width:34px;height:34px;border-radius:11px}
.kcol .kosong-t{font-size:12px}

/* ===== Kanban: header tetap, hanya daftar kartunya yang bergulir =====
   Kolomnya sendiri tidak bergulir, jadi scrollbar mulai persis di bawah
   header dan tidak ada kartu yang menyembul melewatinya. */
.kcol{display:flex;flex-direction:column;max-height:70vh;overflow:hidden}
.kcol-h{flex:none;margin-bottom:9px;padding-bottom:9px;border-bottom:1px solid var(--line)}
.kcol-body{flex:1;min-height:0;overflow-y:auto;margin:0 -4px;padding:0 4px;display:flex;flex-direction:column}

/* ===== Toolbar daftar pekerjaan (cari + filter) ===== */
.dp-bar{display:flex;align-items:center;gap:9px;flex-wrap:wrap;padding:12px 17px;border-bottom:1px solid var(--line);background:#fcfcfe}
/* Kotak cari melar, tapi punya lebar minimum supaya placeholder-nya tetap
   terbaca saat filter di sebelahnya banyak — barisnya boleh membungkus. */
.dp-cari{position:relative;flex:1 1 260px;min-width:240px}
.dp-cari input{width:100%;height:36px;border:1px solid var(--line2);border-radius:9px;padding:0 11px 0 33px;font-size:13px;font-family:inherit;background:#fff;color:var(--ink)}
/* Tinggi seragam: input cari, select filter, dan tombol reset. */
.dp-bar .i-sel-btn,.adm-bar .i-sel-btn{height:36px;padding-top:0;padding-bottom:0}
.dp-bar .btn,.adm-bar .btn{height:36px;padding-top:0;padding-bottom:0}
.dp-cari input:focus{outline:none;border-color:var(--primary)}
.dp-cari svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--muted3);pointer-events:none}
.dp-sel{min-width:152px}
.dp-reset{flex:none}

/* ===== Kolom tengah khusus halaman admin =====
   Dipisah dari .page-col supaya komposisi halaman di sisi user tidak terganggu. */
.content-admin{max-width:1380px;margin:24px auto;width:100%}

/* ===== Toolbar & pagination server-side (halaman admin) ===== */
.adm-bar{display:flex;align-items:center;gap:9px;flex-wrap:wrap;padding:12px 17px;border-bottom:1px solid var(--line);background:#fcfcfe}
.adm-bar .dp-cari{flex:1;min-width:200px}

/* ===== Transkrip percakapan (detail admin) ===== */
.trx{display:flex;flex-direction:column;gap:16px;max-width:820px;margin:0 auto}
.trx-row{display:flex;gap:11px}
.trx-row.user{flex-direction:row-reverse}
.trx-av{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;flex:none;font-size:11px;font-weight:700}
.trx-av.ai{background:#fff;border:1px solid var(--line);padding:3px}
.trx-av.ai img{width:100%;height:100%;object-fit:contain;display:block}
.trx-in{min-width:0;max-width:min(600px,80%)}
.trx-nama{font-size:11.5px;font-weight:700;color:var(--muted2);margin-bottom:5px}
.trx-row.user .trx-nama{text-align:right}
.trx-bub{border-radius:14px;padding:11px 14px;font-size:13.5px;line-height:1.65;white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid var(--line2)}
.trx-row.user .trx-bub{background:var(--primary-soft);border-color:transparent;border-bottom-right-radius:5px}
.trx-row.ai .trx-bub{border-bottom-left-radius:5px}
.trx-t{font-size:10.5px;color:var(--muted3);margin-top:6px}
.trx-row.user .trx-t{text-align:right}
.trx-opsi{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.trx-opsi span{background:var(--line3);color:var(--muted);border-radius:7px;padding:2px 8px;font-size:11px}

/* ===== Kanban (dipakai halaman Pekerjaan Saya & detail proyek admin) ===== */
.kanban{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:11px}
.kcol{background:var(--bg);border-radius:12px;padding:10px;min-height:130px}
.kcol-h{display:flex;align-items:center;justify-content:space-between;font-size:12px;font-weight:700;margin-bottom:9px}
.kcol-c{background:#fff;border:1px solid var(--line2);border-radius:999px;padding:1px 8px;font-size:11px;color:var(--muted2)}
.ktask{background:#fff;box-shadow: 0 0 3px 1px rgba(0, 0, 0, 0.05);border-radius:10px;padding:9px 10px;margin-bottom:8px;cursor:grab}
.ktask.drag{opacity:.45}
.kcol.over{border-color:var(--primary);background:var(--primary-soft)}
.ktask-j{font-size:12.5px;font-weight:600;line-height:1.35}
.ktask-m{display:flex;align-items:center;justify-content:space-between;margin-top:7px;gap:6px}
.ktask-p{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ktask-p .kdot{width:7px;height:7px;border-radius:2px;flex:none}
/* Keadaan muatan bertahap di dasar kolom kanban. */
.kb-state{display:flex;align-items:center;justify-content:center;gap:6px;font-size:11px;color:var(--muted3);padding:8px 4px;text-align:center}
.kb-state.load{color:var(--muted2)}
.kb-spin{width:12px;height:12px;border:2px solid var(--line2);border-top-color:var(--primary);border-radius:50%;animation:kbspin .7s linear infinite;flex:none}
@keyframes kbspin{to{transform:rotate(360deg)}}
.kcol-n{display:flex;align-items:center;gap:7px}
.kdot{width:9px;height:9px;border-radius:50%;display:block;flex:none}
.ktask-w{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;color:var(--muted3);white-space:nowrap;flex:none}
.ktask-ev{font-size:10.5px;color:var(--muted3);margin-top:4px}
.ev-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}
.ev-chip{display:inline-flex;align-items:center;gap:5px;background:var(--line3);border-radius:7px;padding:3px 8px;font-size:11px;color:var(--muted)}
@media(max-width:1000px){.kanban{grid-template-columns:repeat(2,1fr)}}

/* ===== Ringkasan angka di dalam card (bukan KPI penuh) ===== */
.ringkas-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(168px,1fr));gap:9px}
.ringkas-item{display:flex;align-items:center;gap:10px;border:1px solid var(--line2);border-radius:11px;padding:10px 12px;background:#fcfcfe}
.ringkas-m{display:flex;flex-direction:column;min-width:0}
.ringkas-v{font-size:18px;font-weight:800;letter-spacing:-.02em;line-height:1.15}
.ringkas-l{font-size:11.5px;color:var(--muted2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ===== Footer aksi pada kartu tim ===== */
.ucard-f{display:flex;gap:7px;margin-top:13px;padding-top:12px;border-top:1px solid var(--line)}
.ucard-f .btn{flex:1;justify-content:center}
.ucard-f .btn:last-child:hover{background:var(--st-blok-bg);color:var(--st-blok);border-color:var(--st-blok)}

/* ===== Sakelar (toggle) ===== */
/* `.fld label` lebih spesifik daripada `.sw-row`, jadi sakelar di dalam field
   sempat dipaksa display:block dan tumpang tindih dengan teksnya. */
.sw-row,.fld label.sw-row{display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:500;color:var(--muted);margin-bottom:0}
.sw-teks{font-size:13px;font-weight:500;color:var(--muted)}
.sw-row input{position:absolute;opacity:0;width:0;height:0}
.sw-track{width:38px;height:22px;border-radius:999px;background:var(--line2);position:relative;flex:none;transition:background .15s}
.sw-dot{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .15s;box-shadow:0 1px 3px rgba(30,33,48,.25)}
.sw-row input:checked + .sw-track{background:var(--st-done)}
.sw-row input:checked + .sw-track .sw-dot{transform:translateX(16px)}
.sw-row input:disabled + .sw-track{opacity:.5;cursor:not-allowed}

/* ===== Baris kinerja yang bisa diklik (drilldown laporan) ===== */
/* ===== Baris overview (Dashboard & Laporan) ===== */
.ov-row{display:grid;grid-template-columns:170px 1fr 92px 26px;gap:12px;align-items:center;padding:8px 9px;margin:0 -9px;border-bottom:1px solid var(--line3);border-radius:10px;cursor:pointer;transition:background .12s,box-shadow .12s}
.ov-row:hover{background:var(--line3)}
.ov-row.on{background:var(--primary-soft);box-shadow:inset 0 0 0 1px var(--primary)}
.ov-row:focus-visible{outline:2px solid var(--primary);outline-offset:2px}
.ov-row:last-child{border-bottom:none}
.ov-nama{font-size:13px;font-weight:600;display:flex;align-items:center;gap:7px;min-width:0}
.ov-dot{width:9px;height:9px;border-radius:3px;flex:none}
.ov-meta{font-size:11.5px;color:var(--muted2);text-align:right;white-space:nowrap}
/* Tombol buka proyek hanya ada di kartu proyek; baris user menyisakan kolom kosong. */
.ov-row > .ico-btn.xs{opacity:0;transition:opacity .12s}
.ov-row:hover > .ico-btn.xs,.ov-row:focus-within > .ico-btn.xs{opacity:1}
.legend{display:flex;gap:14px;font-size:11.5px;color:var(--muted2);margin-bottom:10px;flex-wrap:wrap}
.legend span{display:flex;align-items:center;gap:5px}
.legend i{width:9px;height:9px;border-radius:3px;display:block}
</style>
