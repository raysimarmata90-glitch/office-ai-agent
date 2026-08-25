<style>
:root{
--primary:#f55d14; --primary-soft:#fef1e9; --primary-dark:#d94c08;
--ink:#1e2130; --muted:#5b6172; --muted2:#8a90a3; --muted3:#a7acbd;
--line:#eaecf3; --line2:#e6e8f0; --line3:#eef0f6;
--bg:#f6f7fb; --card:#ffffff;
--done-bg:#dcefe6; --done-fg:#1f7a52;
--prog-bg:#fdeadb; --prog-fg:#a05a1c;
--rev-bg:#dde9fd;  --rev-fg:#2c5cc5;
--blok-bg:#fde3e1; --blok-fg:#b23c35;
--todo-bg:#eef0f6; --todo-fg:#5b6172;
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
.uf-av{width:30px;height:30px;border-radius:8px;background:var(--primary);color:#fff;display:grid;place-items:center;font-weight:700;font-size:11.5px;flex:none}
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
.content{padding:22px 26px 40px;flex:1}
.card{background:#fff;border:1px solid var(--line);border-radius:14px}
.card-head{padding:15px 17px 12px;border-bottom:1px solid var(--line)}
.card-title{font-size:14.5px;font-weight:700}
.card-desc{font-size:12px;color:var(--muted2);margin-top:3px;line-height:1.5}
.card-body{padding:15px 17px}
.card-foot{padding:11px 17px;border-top:1px solid var(--line);font-size:12px;color:var(--muted2);background:#fcfcfe;border-radius:0 0 14px 14px}
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
.pg-btn:disabled{opacity:.42;cursor:not-allowed}
.avatar{width:28px;height:28px;border-radius:8px;background:var(--primary-soft);color:var(--primary);display:grid;place-items:center;font-weight:700;font-size:11px;flex:none}
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
.sb-hist-btn{display:none}
.sidebar.collapsed .sb-hist-btn{display:flex;justify-content:center;padding:9px 0;margin-top:3px}
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
.i-sel-pop{position:absolute;z-index:120;top:calc(100% + 5px);left:0;right:0;background:#fff;border:1px solid var(--line);border-radius:11px;box-shadow:0 14px 34px rgba(30,33,48,.16);padding:6px;display:none}
.i-sel.open .i-sel-pop{display:block}
.i-sel.up .i-sel-pop{top:auto;bottom:calc(100% + 5px)}
.i-sel-search{display:flex;align-items:center;gap:7px;border:1px solid var(--line2);border-radius:8px;padding:6px 9px;margin-bottom:5px;color:var(--muted3)}
.i-sel-search input{border:none;outline:none;width:100%;font-family:inherit;font-size:12.5px;background:transparent;color:var(--ink)}
.i-sel-list{max-height:208px;overflow-y:auto;display:flex;flex-direction:column;gap:2px}
.i-sel-opt{display:flex;align-items:center;gap:8px;padding:8px 9px;border-radius:8px;font-size:13px;cursor:pointer;color:var(--ink)}
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
.i-up-fi{width:30px;height:30px;border-radius:8px;background:var(--line3);color:var(--muted);display:grid;place-items:center;flex:none}
.i-up-item.bad .i-up-fi{background:#fde3e1;color:#b23c35}
.i-up-meta{flex:1;min-width:0}
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
.gantt-scroll{overflow-x:auto}
.gantt-head{display:grid;grid-template-columns:170px 1fr;gap:11px;border-bottom:1px solid var(--line);padding-bottom:7px;margin-bottom:11px;min-width:560px}
.gantt-cols{display:grid}
.gantt-cols div{font-size:11px;color:var(--muted2);font-weight:600;text-align:center;white-space:nowrap;overflow:hidden}
.gantt-row{display:grid;grid-template-columns:170px 1fr;gap:11px;align-items:center;margin-bottom:9px;min-width:560px}
.gantt-nama{font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gantt-track{position:relative;height:18px;background:var(--line3);border-radius:999px;overflow:hidden}
.gantt-fill{position:absolute;top:0;height:18px;border-radius:999px}
.gantt-now{position:absolute;top:0;bottom:0;width:2px;background:#fff;box-shadow:0 0 0 1px rgba(30,33,48,.42);border-radius:2px;z-index:2}
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
.dlg-head{padding:18px 20px 12px;display:flex;align-items:flex-start;gap:13px}
.dlg-ico{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;flex:none;background:var(--primary-soft);color:var(--primary)}
.dlg-ico.bahaya{background:var(--blok-bg);color:var(--blok-fg)}
.dlg-ico.peringatan{background:var(--prog-bg);color:var(--prog-fg)}
.dlg-judul{font-size:15px;font-weight:800;letter-spacing:-.01em;padding-top:8px}
.dlg-teks{padding:0 20px 18px 71px;font-size:13px;color:var(--muted);line-height:1.6}
.dlg-foot{padding:13px 20px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:9px;background:#fcfcfe}
.btn-danger{background:#b23c35;border-color:#b23c35;color:#fff}
.btn-danger:hover{background:#9a332d}
@media(max-width:520px){.dlg-teks{padding-left:20px}}
</style>
