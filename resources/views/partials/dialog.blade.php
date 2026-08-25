{{-- Komponen global: popup dialog (judul + ikon, isi, aksi footer). Satu instance per halaman. --}}
<div class="dlg-bg" id="inaaiDialog" role="dialog" aria-modal="true" aria-labelledby="inaaiDialogJudul" aria-describedby="inaaiDialogTeks">
<div class="dlg">
<div class="dlg-head">
<span class="dlg-ico" data-dlg-ico></span>
<div style="flex:1;min-width:0">
<div class="dlg-judul" id="inaaiDialogJudul" data-dlg-judul></div>
</div>
</div>
<div class="dlg-teks" id="inaaiDialogTeks" data-dlg-teks></div>
<div class="dlg-foot">
<button type="button" class="btn" data-dlg-batal>Batal</button>
<button type="button" class="btn btn-primary" data-dlg-ok>Konfirmasi</button>
</div>
</div>
</div>
