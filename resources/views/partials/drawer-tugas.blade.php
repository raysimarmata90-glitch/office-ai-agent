{{-- Drawer detail tugas untuk halaman admin. Mengekspos window.InaaiDrawerTugas. --}}
<div class="drw-bg" id="tugasDrawer">
<aside class="drw" role="dialog" aria-modal="true" aria-labelledby="drwTugasJudul">
<div class="drw-head">
<span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></span>
<div class="drw-t" id="drwTugasJudul">Detail Tugas</div>
<button type="button" class="hist-fly-x" data-drwt-close aria-label="Tutup detail">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
</button>
</div>
<div class="drw-body" id="drwTugasBody"></div>
<div class="drw-foot" id="drwTugasFoot"></div>
</aside>
</div>

<script>
(function(){
const token=document.querySelector('meta[name="csrf-token"]').content;
const drw=document.getElementById('tugasDrawer'),
      body=document.getElementById('drwTugasBody'),
      foot=document.getElementById('drwTugasFoot'),
      judul=document.getElementById('drwTugasJudul');

function esc(v){return String(v==null?'':v).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]))}
function ukuran(b){
if(window.InaaiUpload&&window.InaaiUpload.ukuran)return window.InaaiUpload.ukuran(b||0);
return (b||0)+' B';
}
function jenisBerkas(nama,mime){
if(window.InaaiUpload&&window.InaaiUpload.jenisFile)return window.InaaiUpload.jenisFile({name:nama||'',type:mime||''});
return {label:'Berkas',kelas:'lain',ikon:''};
}
function tutup(){drw.classList.remove('open');document.body.style.overflow=''}
drw.addEventListener('click',e=>{if(e.target===drw)tutup()});
document.querySelectorAll('[data-drwt-close]').forEach(b=>b.addEventListener('click',tutup));
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&drw.classList.contains('open'))tutup()});

function baris(label,isi){return '<div class="drw-row"><div class="drw-k">'+esc(label)+'</div><div class="drw-v">'+isi+'</div></div>'}

async function buka(id){
body.innerHTML='<div class="empty">Memuat detail…</div>';
foot.innerHTML='';
drw.classList.add('open');
document.body.style.overflow='hidden';
let d;
try{
const r=await fetch('/tasks/'+id,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
if(!r.ok)throw new Error(r.status);
d=await r.json();
}catch(e){body.innerHTML='<div class="empty">Gagal memuat detail tugas.</div>';return}

judul.textContent=d.judul||'Detail Tugas';
const w=d.status_warna||{};
let html='';
html+=baris('Status','<span class="badge" style="background:'+esc(w.bg)+';color:'+esc(w.text)+'">'+esc(d.status_label)+'</span>');
html+=baris('Proyek','<span style="display:inline-flex;align-items:center;gap:7px"><i style="width:8px;height:8px;border-radius:2px;background:'+esc(d.warna||'#f55d14')+';display:block"></i>'+esc(d.proyek||'–')+'</span>');
html+=baris('Prioritas',esc(d.prioritas||'–'));
html+=baris('Periode',esc((d.mulai||'–')+' – '+(d.selesai||'–')));
html+=baris('Assign ke',esc(d.pemilik||'–'));
html+=baris('Reviewer',esc(d.reviewer||'–'));
html+=baris('Dibuat',esc(d.dibuat||'–'));
html+=baris('Diubah',esc(d.diubah||'–'));
if(d.deskripsi)html+=baris('Deskripsi','<span style="white-space:pre-wrap">'+esc(d.deskripsi)+'</span>');

const ev=d.evidences||[];
html+='<div class="drw-sec">Evidence <span class="drw-n">'+ev.length+'</span></div>';
html+=ev.length?('<div class="drw-ev">'+ev.map(function(e){
const j=jenisBerkas(e.nama,e.mime);
return '<div class="drw-evi">'+
'<span class="i-up-fi '+j.kelas+'">'+j.ikon+'</span>'+
'<span class="i-up-meta"><span class="i-up-fn" title="'+esc(e.nama)+'">'+esc(e.nama)+'</span>'+
'<span class="i-up-fs">'+esc(j.label)+' · '+esc(ukuran(e.ukuran))+'</span></span>'+
'<a class="ico-btn" href="'+esc(e.url)+'" target="_blank" rel="noopener" title="Lihat file" aria-label="Lihat file">'+
'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></a>'+
'<a class="ico-btn" href="'+esc(e.unduh)+'" title="Unduh file" aria-label="Unduh file">'+
'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></svg></a>'+
'</div>';
}).join('')+'</div>')
:'<div class="kosong" style="min-height:120px"><span class="kosong-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></span><span class="kosong-t">Belum ada evidence</span></div>';

body.innerHTML=html;

const IKO_X='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
const IKO_SAMPAH='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
const IKO_BUKA='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14 21 3"/></svg>';
foot.innerHTML=
(d.boleh_edit?'<button type="button" class="btn btn-danger" data-drwt-hapus="'+d.id+'" data-judul="'+esc(d.judul)+'">'+IKO_SAMPAH+'Hapus</button>':'')+
'<span class="drw-foot-sela"></span>'+
'<button type="button" class="btn" data-drwt-close>'+IKO_X+'Tutup</button>'+
'<a class="btn btn-primary" href="/admin/proyek/'+esc(d.form.project_id)+'">'+IKO_BUKA+'Buka Proyek</a>';
foot.querySelectorAll('[data-drwt-close]').forEach(b=>b.addEventListener('click',tutup));
const bh=foot.querySelector('[data-drwt-hapus]');
if(bh)bh.addEventListener('click',()=>{tutup();hapus(d.id,d.judul||'')});
}

async function hapus(id,nama){
const lanjut=window.InaaiDialog?await window.InaaiDialog.konfirmasi({
judul:'Hapus Tugas',
teks:'Tugas "'+nama+'" beserta seluruh file evidence-nya akan dihapus permanen.',
ok:'Iya, hapus tugas',jenis:'bahaya'
}):confirm('Hapus tugas ini?');
if(!lanjut)return;
try{
const r=await fetch('/tasks/'+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
const d=await r.json();
if(!r.ok){window.InaaiToast&&window.InaaiToast.galat(d.message||'Tugas gagal dihapus.');return}
window.InaaiToast&&window.InaaiToast.sukses(d.pesan);
setTimeout(()=>location.reload(),650);
}catch(e){window.InaaiToast&&window.InaaiToast.galat('Koneksi bermasalah: '+e.message)}
}

window.InaaiDrawerTugas={buka:buka,hapus:hapus};
})();
</script>
