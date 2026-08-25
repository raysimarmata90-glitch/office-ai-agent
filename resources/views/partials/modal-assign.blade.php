<div class="modal-bg" id="assignModal">
<div class="modal">
<form method="POST" action="{{ route('admin.tugas.assign') }}">@csrf
<div class="modal-head">
<div style="flex:1">
<div class="card-title">Assign Tugas ke Pegawai</div>
<div class="card-desc">Pilih proyek yang sudah ada atau buat proyek baru, lalu tentukan pemilik dan reviewer.</div>
</div>
<button type="button" class="btn btn-sm" data-close-assign>✕</button>
</div>
<div class="modal-body">
<div class="fld">
<label>Judul Tugas</label>
<input type="text" name="judul" required placeholder="Contoh: Integrasi API pembayaran" value="{{ old('judul') }}">
</div>
<div class="row2">
<div class="fld">
<label>Proyek</label>
<select name="project_id" id="assignProject">
<option value="">— Proyek baru —</option>
@foreach($projects as $p)
<option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>{{ $p->nama }}</option>
@endforeach
</select>
</div>
<div class="fld">
<label>Nama Proyek Baru</label>
<input type="text" name="project_baru" id="assignProjectBaru" placeholder="Isi bila proyek baru" value="{{ old('project_baru') }}">
</div>
</div>
<div class="row2">
<div class="fld">
<label>Assign ke</label>
<select name="user_id" required>
@foreach($semuaUser as $su)
<option value="{{ $su->id }}" @selected(old('user_id') == $su->id)>{{ $su->name }}</option>
@endforeach
</select>
</div>
<div class="fld">
<label>Reviewer</label>
<select name="reviewer_id">
<option value="">— Tanpa reviewer —</option>
@foreach($semuaUser as $su)
<option value="{{ $su->id }}" @selected(old('reviewer_id') == $su->id)>{{ $su->name }}</option>
@endforeach
</select>
</div>
</div>
<div class="row2">
<div class="fld">
<label>Status Awal</label>
<select name="status" required>
@foreach(\App\Models\Task::daftarStatus() as $k => $lbl)
<option value="{{ $k }}" @selected(old('status', 'to_do') === $k)>{{ $lbl }}</option>
@endforeach
</select>
</div>
<div class="fld">
<label>Prioritas</label>
<select name="prioritas" required>
@foreach(['Tinggi','Sedang','Rendah'] as $pr)
<option value="{{ $pr }}" @selected(old('prioritas', 'Sedang') === $pr)>{{ $pr }}</option>
@endforeach
</select>
</div>
</div>
<div class="row2">
<div class="fld">
<label>Waktu Mulai</label>
<input type="date" name="mulai" required value="{{ old('mulai', now()->toDateString()) }}">
</div>
<div class="fld">
<label>Waktu Selesai</label>
<input type="date" name="selesai" required value="{{ old('selesai', now()->addWeeks(2)->toDateString()) }}">
</div>
</div>
<div class="fld">
<label>Deskripsi</label>
<textarea name="deskripsi" rows="3" placeholder="Detail pekerjaan (opsional)">{{ old('deskripsi') }}</textarea>
</div>
</div>
<div class="modal-foot">
<button type="button" class="btn" data-close-assign>Batal</button>
<button type="submit" class="btn btn-primary">Simpan Tugas</button>
</div>
</form>
</div>
</div>
<script>
(function(){
const m=document.getElementById('assignModal');
document.querySelectorAll('[data-open-assign]').forEach(function(b){b.addEventListener('click',function(){m.classList.add('open')})});
document.querySelectorAll('[data-close-assign]').forEach(function(b){b.addEventListener('click',function(){m.classList.remove('open')})});
m.addEventListener('click',function(e){if(e.target===m)m.classList.remove('open')});
const sel=document.getElementById('assignProject'),baru=document.getElementById('assignProjectBaru');
function sync(){baru.disabled=!!sel.value;if(sel.value)baru.value=''}
sel.addEventListener('change',sync);sync();
@if($errors->any() && old('judul'))
m.classList.add('open');
@endif
})();
</script>
