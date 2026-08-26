{{-- Form assign tugas: komponen dan gaya mengikuti form "Tugas Baru" di sisi user. --}}
<x-modal id="assignModal" lebar="620px" judul="Assign Tugas ke Pegawai"
         desc="Lengkapi detail, tentukan pemilik dan reviewer, lalu simpan."
         :ikon="'<svg width=\'15\' height=\'15\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M12 20h9\'/><path d=\'M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z\'/></svg>'">
<form method="POST" action="{{ route('admin.tugas.assign') }}" class="modal-form" id="assignForm">@csrf
<input type="hidden" name="_method" id="assignMethod" disabled>
<div class="modal-body">
<div class="fld">
<label>Judul Tugas</label>
<input type="text" name="judul" required placeholder="Contoh: Integrasi API pembayaran" value="{{ old('judul') }}">
</div>
<div class="row2">
<div class="fld">
<label>Proyek</label>
<select name="project_id" id="assignProject" data-select data-placeholder="Pilih proyek">
<option value="">— Proyek baru —</option>
@foreach($projects as $p)
<option value="{{ $p->id }}" data-color="{{ $p->warna }}" @selected(old('project_id') == $p->id)>{{ $p->nama }}</option>
@endforeach
</select>
</div>
<div class="fld" id="assignProjectBaruFld">
<label>Nama Proyek Baru</label>
<input type="text" name="project_baru" id="assignProjectBaru" placeholder="Isi nama proyek baru" value="{{ old('project_baru') }}">
</div>
</div>
<div class="row2">
<div class="fld">
<label>Status Saat Ini</label>
<select name="status" required data-select data-placeholder="Pilih status">
@foreach(\App\Models\Task::daftarStatus() as $k => $lbl)
<option value="{{ $k }}" data-color="{{ \App\Models\Task::titikStatus($k) }}" @selected(old('status', 'to_do') === $k)>{{ $lbl }}</option>
@endforeach
</select>
</div>
<div class="fld">
<label>Prioritas</label>
<select name="prioritas" required data-select data-placeholder="Pilih prioritas">
@foreach(\App\Models\Task::daftarPrioritas() as $pr => $warna)
<option value="{{ $pr }}" data-color="{{ $warna }}" @selected(old('prioritas', 'Sedang') === $pr)>{{ $pr }}</option>
@endforeach
</select>
</div>
</div>
<div class="row2">
<div class="fld">
<label>Waktu Mulai</label>
<input type="text" name="mulai" id="assignMulai" required data-datepicker value="{{ old('mulai', now()->toDateString()) }}">
</div>
<div class="fld">
<label>Waktu Selesai</label>
<input type="text" name="selesai" id="assignSelesai" required data-datepicker data-min="{{ old('mulai', now()->toDateString()) }}" value="{{ old('selesai', now()->addWeeks(2)->toDateString()) }}">
</div>
</div>
<div class="row2">
<div class="fld">
<label>Assign ke</label>
<select name="user_id" required data-select data-placeholder="Pilih pegawai">
@foreach($semuaUser as $su)
<option value="{{ $su->id }}" @selected(old('user_id') == $su->id)>{{ $su->name }}</option>
@endforeach
</select>
</div>
<div class="fld">
<label>Request Reviewer</label>
<select name="reviewer_id" data-select data-placeholder="Pilih reviewer">
<option value="">— Tanpa reviewer —</option>
@foreach($semuaUser as $su)
<option value="{{ $su->id }}" @selected(old('reviewer_id') == $su->id)>{{ $su->name }}</option>
@endforeach
</select>
</div>
</div>
<div class="fld">
<label>Deskripsi</label>
<textarea name="deskripsi" rows="3" placeholder="Detail pekerjaan (opsional)">{{ old('deskripsi') }}</textarea>
</div>
{{-- Hanya terisi pada mode ubah: file yang sudah melekat pada tugas. --}}
<div class="fld" id="assignEvidenceLama" style="display:none">
<label>File Saat Ini</label>
<div class="drw-ev" id="assignEvidenceList"></div>
</div>
<div class="fld">
<label>Tambah Evidence (dokumen / gambar)</label>
<input type="file" name="evidence[]" multiple
       data-upload
       data-max-size="{{ \App\Support\BatasUnggah::maksMb() }}"
       data-judul="Seret file ke sini atau <b>pilih dari perangkat</b>"
       accept="{{ \App\Support\BatasUnggah::accept() }}">
</div>
</div>
<div class="modal-foot">
<button type="button" class="btn" data-modal-close>
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
Batal
</button>
<button type="submit" class="btn btn-primary">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
<span id="assignSubmitLabel">Simpan Tugas</span>
</button>
</div>
</form>
</x-modal>

<script>
(function(){
const m=document.getElementById('assignModal');
document.querySelectorAll('[data-open-assign]').forEach(function(b){b.addEventListener('click',function(){m.classList.add('open');document.body.style.overflow='hidden'})});
function tutup(){m.classList.remove('open');document.body.style.overflow=''}
m.querySelectorAll('[data-modal-close]').forEach(function(b){b.addEventListener('click',tutup)});
m.addEventListener('click',function(e){if(e.target===m)tutup()});
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&m.classList.contains('open'))tutup()});

const sel=document.getElementById('assignProject'),baru=document.getElementById('assignProjectBaru'),
      baruFld=document.getElementById('assignProjectBaruFld');
function sync(){
const proyekBaru=!sel.value;
baruFld.style.display=proyekBaru?'':'none';
baru.disabled=!proyekBaru;
if(!proyekBaru)baru.value='';
}
sel.addEventListener('change',sync);sync();

// Waktu selesai mengikuti batas bawah waktu mulai.
const dm=document.getElementById('assignMulai'),ds=document.getElementById('assignSelesai');
if(dm&&ds)dm.addEventListener('change',function(){
if(ds.inaaiDt)ds.inaaiDt.aturMin(dm.value);
if(dm.value&&ds.value&&ds.value<dm.value){ds.value=dm.value;ds.dispatchEvent(new Event('change',{bubbles:true}))}
});

@if($errors->any() && old('judul'))
m.classList.add('open');
@endif

/* ===== Mode ubah: form yang sama dipakai ulang lewat PATCH /tasks/{id} ===== */
const form=document.getElementById('assignForm'),metode=document.getElementById('assignMethod'),
      labelSimpan=document.getElementById('assignSubmitLabel'),
      judulModal=document.getElementById('assignModalJudul'),
      descModal=form.closest('.modal').querySelector('.card-desc'),
      aksiBuat=form.getAttribute('action');

function setSel(nama,nilai){
const el=form.querySelector('[name="'+nama+'"]');
if(!el)return;
el.value=nilai==null?'':String(nilai);
el.dispatchEvent(new Event('change',{bubbles:true}));
if(el.inaaiSel&&el.inaaiSel.segarkan)el.inaaiSel.segarkan();
}

/* Daftar file yang sudah melekat pada tugas, lengkap tombol lihat dan hapus.
   Ikon dan format ukurannya memakai komponen unggahan yang sama. */
function esc(v){return String(v==null?'':v).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]))}
function jenisBerkas(nama,mime){
if(window.InaaiUpload&&window.InaaiUpload.jenisFile)return window.InaaiUpload.jenisFile({name:nama||'',type:mime||''});
return {kelas:'lain',ikon:'',label:'File'};
}
function ukuranBerkas(b){
if(window.InaaiUpload&&window.InaaiUpload.ukuran)return window.InaaiUpload.ukuran(b);
return (b||0)+' B';
}

function gambarEvidence(daftar){
const fld=document.getElementById('assignEvidenceLama'),list=document.getElementById('assignEvidenceList');
if(!daftar||!daftar.length){fld.style.display='none';list.innerHTML='';return}
fld.style.display='';
list.innerHTML=daftar.map(function(e){
const j=jenisBerkas(e.nama,e.mime);
return '<div class="drw-evi" data-ev="'+e.id+'">'+
'<span class="i-up-fi '+j.kelas+'">'+j.ikon+'</span>'+
'<span class="i-up-meta"><span class="i-up-fn" title="'+esc(e.nama)+'">'+esc(e.nama)+'</span>'+
'<span class="i-up-fs">'+esc(j.label)+' · '+esc(ukuranBerkas(e.ukuran))+'</span></span>'+
'<a class="ico-btn" href="'+esc(e.url)+'" target="_blank" rel="noopener" title="Lihat file" aria-label="Lihat file">'+
'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></a>'+
'<button type="button" class="ico-btn" data-hapus-ev="'+e.id+'" data-nama="'+esc(e.nama)+'" title="Hapus file" aria-label="Hapus file">'+
'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>'+
'</div>';
}).join('');

list.querySelectorAll('[data-hapus-ev]').forEach(function(b){
b.addEventListener('click',async function(){
const lanjut=window.InaaiDialog?await window.InaaiDialog.konfirmasi({
judul:'Hapus File Evidence',
teks:'File "'+b.dataset.nama+'" akan dihapus permanen dari tugas ini.',
ok:'Iya, hapus file',jenis:'bahaya'
}):confirm('Hapus file ini?');
if(!lanjut)return;
try{
const token=document.querySelector('meta[name="csrf-token"]').content;
const r=await fetch('/evidence/'+b.dataset.hapusEv,{method:'DELETE',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
const d=await r.json();
if(!r.ok){window.InaaiToast&&window.InaaiToast.galat(d.message||'File gagal dihapus.');return}
const baris=list.querySelector('[data-ev="'+b.dataset.hapusEv+'"]');
if(baris)baris.remove();
if(!list.querySelector('[data-ev]'))fld.style.display='none';
window.InaaiToast&&window.InaaiToast.sukses(d.pesan);
}catch(e){window.InaaiToast&&window.InaaiToast.galat('Koneksi bermasalah: '+e.message)}
});
});
}

function kosongkanUnggahan(){
const el=form.querySelector('input[type="file"][data-upload]');
if(el&&el.inaaiUpload)el.inaaiUpload.kosongkan();
}

function modeBuat(){
form.setAttribute('action',aksiBuat);
kosongkanUnggahan();
metode.value='';metode.disabled=true;
judulModal.textContent='Assign Tugas ke Pegawai';
if(descModal)descModal.textContent='Lengkapi detail, tentukan pemilik dan reviewer, lalu simpan.';
labelSimpan.textContent='Simpan Tugas';
gambarEvidence([]);
}

async function modeUbah(id){
let d;
try{
const r=await fetch('/tasks/'+id,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
if(!r.ok)throw new Error(r.status);
d=await r.json();
}catch(e){window.InaaiToast&&window.InaaiToast.galat('Gagal memuat data tugas: '+e.message);return}

form.setAttribute('action','/tasks/'+id);
metode.value='PATCH';metode.disabled=false;
judulModal.textContent='Ubah Tugas';
if(descModal)descModal.textContent='Perbarui detail tugas, pemilik, dan reviewernya.';
labelSimpan.textContent='Simpan Perubahan';

form.querySelector('[name="judul"]').value=d.judul||'';
form.querySelector('[name="deskripsi"]').value=d.deskripsi||'';
setSel('project_id',d.form.project_id);
setSel('status',d.status);
setSel('prioritas',d.prioritas);
setSel('user_id',d.form.user_id);
setSel('reviewer_id',d.form.reviewer_id);
const im=form.querySelector('[name="mulai"]'),is=form.querySelector('[name="selesai"]');
if(im){im.value=d.form.mulai||'';im.dispatchEvent(new Event('change',{bubbles:true}))}
if(is){is.value=d.form.selesai||'';is.dispatchEvent(new Event('change',{bubbles:true}))}
gambarEvidence(d.evidences||[]);
kosongkanUnggahan();
sync();
m.classList.add('open');document.body.style.overflow='hidden';
}

/* Kiriman lewat fetch: halaman admin tetap di tempatnya. Kalau dibiarkan
   sebagai kiriman form biasa, PATCH /tasks/{id} akan mengalihkan ke halaman
   Pekerjaan Saya milik role user. */
form.addEventListener('submit',async function(e){
e.preventDefault();
const tombol=form.querySelector('[type="submit"]');
const unggah=form.querySelector('input[type="file"][data-upload]');
if(unggah&&unggah.inaaiUpload){
const salah=unggah.inaaiUpload.salah();
if(salah.length){
window.InaaiToast&&window.InaaiToast.galat(salah.map(x=>x.nama+' — '+x.pesan).join(' · '),{judul:'File belum memenuhi syarat'});
return;
}
}
const fd=new FormData(form);
if(metode.disabled)fd.delete('_method');
tombol.disabled=true;
try{
const r=await fetch(form.getAttribute('action'),{
method:'POST',
body:fd,
headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
});
const d=await r.json().catch(()=>({}));
if(!r.ok){
let pesan=d.message||'Gagal menyimpan tugas.';
if(d.errors)pesan=Object.values(d.errors).flat().join(' ');
window.InaaiToast&&window.InaaiToast.galat(pesan);
tombol.disabled=false;
return;
}
window.InaaiToast&&window.InaaiToast.sukses(d.pesan||'Tugas tersimpan.');
tutup();
setTimeout(()=>location.reload(),650);
}catch(err){
window.InaaiToast&&window.InaaiToast.galat('Koneksi bermasalah: '+err.message);
tombol.disabled=false;
}
});

document.querySelectorAll('[data-open-assign]').forEach(b=>b.addEventListener('click',modeBuat));
window.InaaiFormTugas={buat:function(){modeBuat();m.classList.add('open');document.body.style.overflow='hidden'},ubah:modeUbah};
})();
</script>
