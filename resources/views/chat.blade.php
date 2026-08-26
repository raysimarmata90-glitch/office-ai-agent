@extends('layouts.user')
@php($judulChat = $conversation?->title ?? 'Percakapan Baru')
@section('title', $judulChat)
@section('page-title', $judulChat)
@section('page-sub', $conversation
    ? (($conversation->department?->name ?? 'Agent') . ' · ' . $conversation->created_at->translatedFormat('d F Y'))
    : 'Mulai percakapan — belum tersimpan')

@section('topbar-actions')
<a href="{{ route('pekerjaan.index') }}" class="btn">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18M9 16l2 2 4-4"/></svg>
Pekerjaan Saya
</a>
@if($conversation)
<form method="POST" action="{{ route('conversations.destroy', $conversation->id) }}"
      data-confirm
      data-confirm-judul="Hapus Percakapan"
      data-confirm-teks="Percakapan &quot;{{ $conversation->namaProyek() }}&quot; beserta seluruh pesannya akan dihapus permanen. Tindakan ini tidak bisa dibatalkan."
      data-confirm-ok="Iya, hapus percakapan"
      data-confirm-jenis="bahaya">
@csrf @method('DELETE')
<button type="submit" class="btn">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
Hapus
</button>
</form>
@endif
@endsection

@push('style')
<style>
/* Layout kolom tengah ala claude.ai */
.chat-wrap{display:flex;flex-direction:column;height:calc(100vh - 132px)}
.msgs{flex:1;overflow-y:auto;padding:10px 0 20px}
.chat-col{max-width:760px;margin:0 auto;width:100%;padding:0 8px}

/* Pesan */
.msg{display:flex;margin-bottom:22px}
.msg.me{justify-content:flex-end}
/* Batas lebar dipasang di flex item, bukan persentase di dalam kotak yang
   lebarnya menyusut mengikuti isi — itu bikin teks pendek pecah per huruf. */
.msg-in{max-width:100%;min-width:0}
.msg.me .msg-in{max-width:min(560px,82%)}
.msg.ai .bub{background:transparent;border:none;padding:0;font-size:15px;line-height:1.72;color:var(--ink)}
.msg.me .bub{background:var(--primary-soft);color:var(--ink);border-radius:18px;border-bottom-right-radius:6px;padding:11px 16px;font-size:14.5px;line-height:1.65}
.bub{white-space:pre-wrap;word-break:break-word}
.bub-t{font-size:10.5px;margin-top:6px;color:var(--muted3)}
.msg.me .bub-t{text-align:right}
.ai-head{display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:12px;color:var(--muted2);font-weight:600}
.ai-av{width:22px;height:22px;border-radius:7px;background:#fff;border:1px solid var(--line);display:grid;place-items:center;flex:none;padding:1px}
.ai-av img{width:100%;height:100%;object-fit:contain;display:block}

/* Pilihan cepat */
.opts{display:grid;gap:7px;margin-top:13px}
.opt{display:flex;align-items:flex-start;gap:11px;width:100%;border:1px solid var(--line2);background:#fff;border-radius:12px;padding:11px 13px;text-align:left;cursor:pointer;font-size:13.5px;font-family:inherit;color:var(--ink)}
.opt:hover{border-color:var(--primary);background:var(--primary-soft)}
.opt-n{width:22px;height:22px;border-radius:7px;background:var(--line3);color:var(--muted);display:grid;place-items:center;font-size:11px;font-weight:700;flex:none}
.opt:hover .opt-n{background:var(--primary);color:#fff}
.opt.other{border-style:dashed;background:#fcfcfe;color:var(--muted)}

/* Composer ala claude.ai */
.composer-zone{padding-top:6px;position:sticky;bottom:0;background:linear-gradient(180deg,rgba(246,247,251,0) 0%,var(--bg) 34%)}
.composer{border:1px solid var(--line2);background:#fff;border-radius:22px;padding:14px 16px 10px;box-shadow:0 3px 16px rgba(30,33,48,.06);transition:border-color .15s,box-shadow .15s}
.composer:focus-within{border-color:var(--primary);box-shadow:0 4px 22px rgba(245,93,20,.13)}
.composer textarea{width:100%;border:none;outline:none;resize:none;font-family:inherit;font-size:15px;line-height:1.6;color:var(--ink);background:transparent;max-height:210px;min-height:26px}
.composer textarea::placeholder{color:var(--muted3)}
.comp-bar{display:flex;align-items:center;gap:7px;margin-top:9px}
.comp-btn{width:33px;height:33px;border-radius:50%;border:1px solid var(--line2);background:#fff;color:var(--muted2);display:grid;place-items:center;cursor:pointer;flex:none}
.comp-btn:hover{background:var(--primary-soft);color:var(--primary);border-color:var(--primary)}
.comp-btn.rec{background:#fde3e1;color:#b23c35;border-color:#f0b8b4}
.comp-send{width:33px;height:33px;border-radius:50%;border:none;background:var(--primary);color:#fff;display:grid;place-items:center;cursor:pointer;flex:none;transition:background .15s,opacity .15s}
.comp-send:hover:not(:disabled){background:var(--primary-dark)}
.comp-send:disabled{background:var(--line2);color:var(--muted3);cursor:not-allowed}
.att-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.att{display:inline-flex;align-items:center;gap:6px;background:var(--line3);border-radius:8px;padding:4px 9px;font-size:11.5px;color:var(--muted)}
.att button{border:none;background:transparent;cursor:pointer;color:var(--muted2);font-size:13px;line-height:1;padding:0}
.chat-err{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--blok-fg);background:var(--blok-bg);border-radius:10px;padding:9px 12px;margin-bottom:9px}
.typing{display:inline-flex;gap:4px;align-items:center}
.typing i{width:7px;height:7px;border-radius:50%;background:var(--primary);display:block;animation:bl 1.3s infinite}
.typing i:nth-child(2){animation-delay:.18s}
.typing i:nth-child(3){animation-delay:.36s}
@keyframes bl{0%,60%,100%{opacity:.3;transform:translateY(0)}30%{opacity:1;transform:translateY(-2px)}}
.done-note{text-align:center;background:var(--done-bg);color:var(--done-fg);border-radius:11px;padding:12px;font-size:13px;max-width:760px;margin:0 auto}
.foot-note{text-align:center;font-size:11px;color:var(--muted3);margin:9px 0 4px}
#composerBox[hidden]{display:none}
.opts.bebas .opt:not(.batal){opacity:.5}
.opt.batal{border-style:dashed;background:#fff;color:var(--muted)}
.opt.batal:hover{border-color:var(--st-blok);background:var(--st-blok-bg);color:var(--st-blok)}
.opt.batal:hover .opt-n{background:var(--st-blok);color:#fff}
.selesai-box{text-align:center;background:#fff;border:1px solid var(--line2);border-radius:16px;padding:20px 18px;box-shadow:0 3px 16px rgba(30,33,48,.06)}
.selesai-ico{width:46px;height:46px;border-radius:50%;background:var(--st-done-bg);color:var(--st-done);display:grid;place-items:center;margin:0 auto 11px;animation:pop .45s cubic-bezier(.2,1.4,.5,1) both}
@keyframes pop{from{transform:scale(.4);opacity:0}to{transform:scale(1);opacity:1}}
.selesai-t{font-size:15px;font-weight:800;letter-spacing:-.01em}
.selesai-s{font-size:12.5px;color:var(--muted2);margin-top:4px}
.selesai-aksi{display:flex;gap:9px;justify-content:center;margin-top:14px;flex-wrap:wrap}
.konfeti{position:fixed;inset:0;pointer-events:none;z-index:400;overflow:hidden}
.konfeti i{position:absolute;top:-14px;width:8px;height:13px;border-radius:2px;display:block;animation:jatuh linear forwards}
@keyframes jatuh{to{transform:translateY(105vh) rotate(760deg);opacity:.15}}
@media(prefers-reduced-motion:reduce){.konfeti{display:none}.selesai-ico{animation:none}}
@media(max-width:720px){.chat-col{padding:0 4px}.msg.ai .bub{font-size:14.5px}}
</style>
@endpush

@php($pesanAiTerakhir = $conversation?->messages->where('sender_type', 'ai')->last())
@php($metaAwal = $pesanAiTerakhir?->metadata ?? \App\Http\Controllers\ChatController::metadataAwal())
@php($opsiAwal = [
    'options' => ($metaAwal['has_options'] ?? false) ? ($metaAwal['options'] ?? []) : [],
    'question_type' => $metaAwal['question_type'] ?? null,
])
@section('content')
<div class="chat-wrap">
<div class="msgs" id="messagesContainer">
<div class="chat-col" id="messageList">
@if($conversation)
@foreach($conversation->messages as $m)
<div class="msg {{ $m->sender_type === 'user' ? 'me' : 'ai' }}" @if($m->sender_type !== 'user') data-ai @endif>
<div class="msg-in">
@if($m->sender_type !== 'user')
<div class="ai-head">
<span class="ai-av"><img src="{{ asset('img/logo-inaai.webp') }}" alt="INaAI"></span>
INaAI Agent
</div>
@endif
<div class="bub">{{ $m->content }}<div class="bub-t">{{ $m->created_at->format('H:i') }}</div></div>
</div>
</div>
@endforeach
@else
{{-- Layar "Chat Baru": pertanyaan pembuka ditampilkan saja, belum disimpan.
     Percakapan baru dibuat setelah user mengirim jawaban pertama. --}}
<div class="msg ai" data-ai>
<div class="msg-in">
<div class="ai-head">
<span class="ai-av"><img src="{{ asset('img/logo-inaai.webp') }}" alt="INaAI"></span>
INaAI Agent
</div>
<div class="bub">{{ \App\Http\Controllers\ChatController::PERTANYAAN_AWAL }}<div class="bub-t">{{ now()->format('H:i') }}</div></div>
</div>
</div>
@endif
</div>
</div>

@if($conversation?->isActive() ?? true)
<div class="composer-zone">
<div class="chat-col">
<div class="chat-err" id="chatError" role="alert" style="display:none"></div>
<div id="composerBox">
<form id="messageForm">
@csrf
<div class="composer">
<textarea id="messageInput" rows="1" placeholder="Tulis pesan untuk INaAI Agent..."></textarea>
<div class="att-list" id="attList"></div>
<div class="comp-bar">
<button type="button" class="comp-btn" id="attachBtn" title="Tambah lampiran" aria-label="Tambah lampiran">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
</button>
<input type="file" id="attachInput" multiple style="display:none" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp">
<button type="button" class="comp-btn" id="voiceBtn" title="Rekam suara" aria-label="Rekam suara" style="margin-left:auto">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2M12 19v3"/></svg>
</button>
<button type="submit" class="comp-send" id="sendButton" title="Kirim" aria-label="Kirim" disabled>
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
</button>
</div>
</div>
</form>
<div class="foot-note">INaAI Agent dapat membuat kesalahan. Periksa kembali informasi penting.</div>
</div>
</div>
</div>
@else
<div class="done-note">Percakapan ini sudah selesai.</div>
@endif
</div>
@endsection

@push('script')
<script>
/* Percakapan bisa belum tersimpan (layar "Chat Baru"): id diisi setelah pesan pertama. */
let conversationId = {{ $conversation?->id ?? 'null' }};
const urlMulai = @json(route('chat.mulai'));
const urlKirim = function () { return '/conversations/' + conversationId + '/messages'; };

/* Pilihan cepat datang dari server (metadata pesan AI), bukan ditebak di klien. */
const opsiAwal = @json($opsiAwal);

const messagesContainer = document.getElementById('messagesContainer');
const messageList = document.getElementById('messageList');
const messageForm = document.getElementById('messageForm');
const messageInput = document.getElementById('messageInput');
const sendButton = document.getElementById('sendButton');
const composerBox = document.getElementById('composerBox');
const chatError = document.getElementById('chatError');
const attachBtn = document.getElementById('attachBtn');
const attachInput = document.getElementById('attachInput');
const attList = document.getElementById('attList');
const voiceBtn = document.getElementById('voiceBtn');

let lampiran = [];

scrollToBottom();

function scrollToBottom() {
if (messagesContainer) messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function escapeHtml(value) {
return String(value).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
}

/* Saringan ringan di klien; validasi sebenarnya tetap di server. */
function jawabanBermakna(value) {
const normal = String(value).trim();
if (normal.length < 2) return false;
const huruf = (normal.match(/[A-Za-zÀ-ÿ]/g) || []).join('');
const alnum = normal.replace(/[^A-Za-zÀ-ÿ0-9]/g, '');
if (alnum.length < 2) return false;
if (huruf.length === 0) return /^[0-9]+$/.test(alnum);
return true;
}

function tampilError(pesan) {
if (!chatError) return;
chatError.textContent = pesan;
chatError.style.display = 'flex';
}

function bersihkanError() {
if (!chatError) return;
chatError.textContent = '';
chatError.style.display = 'none';
}

function conversationIsActive() {
return {{ ($conversation?->isActive() ?? true) ? 'true' : 'false' }};
}

function tampilComposer(tampil) {
if (!composerBox) return;
composerBox.hidden = !tampil;
if (tampil && messageInput) messageInput.focus();
}

function hapusOpsi() {
document.querySelectorAll('.msgs .opts').forEach(function (el) { el.remove(); });
}

/**
 * Render pilihan cepat dari server tepat di bawah pertanyaan AI terakhir,
 * lalu sembunyikan kotak tulis. "Jawaban lain" adalah jalan keluarnya:
 * server memang mengharapkan klien yang menyediakan opsi bebas ini.
 */
function showOptions(options, questionType) {
hapusOpsi();

if (!conversationIsActive()) { tampilComposer(false); return; }

const daftar = (options || []).filter(function (o) {
return String(o).trim() !== '' && String(o).trim().toLowerCase() !== 'something else';
});

if (daftar.length === 0) { tampilComposer(true); return; }

const pesanAi = document.querySelectorAll('.msgs [data-ai] .msg-in');
const induk = pesanAi[pesanAi.length - 1];
if (!induk) { tampilComposer(true); return; }

const kotak = document.createElement('div');
kotak.className = 'opts';
kotak.dataset.questionType = questionType || '';
kotak.innerHTML = daftar.map(function (o, i) {
return '<button type="button" class="opt" data-value="' + escapeHtml(o) + '">' +
'<span class="opt-n">' + (i + 1) + '</span><span>' + escapeHtml(o) + '</span></button>';
}).join('') +
'<button type="button" class="opt other" data-lain>' +
'<span class="opt-n">+</span><span>Jawaban lain</span></button>';
induk.appendChild(kotak);

tampilComposer(false);

function batalkanPilihan() {
kotak.classList.remove('bebas');
var batal = kotak.querySelector('[data-batal]');
if (batal) batal.remove();
tampilComposer(false);
scrollToBottom();
}

kotak.querySelectorAll('.opt').forEach(function (b) {
b.addEventListener('click', function () {
if (b.hasAttribute('data-lain')) {
if (kotak.classList.contains('bebas')) return;
// Pilihan tetap terlihat (diredupkan), kotak tulis dibuka, dan
// disediakan jalan kembali lewat "Batalkan pilihan".
kotak.classList.add('bebas');
var batal = document.createElement('button');
batal.type = 'button';
batal.className = 'opt batal';
batal.setAttribute('data-batal', '');
batal.innerHTML = '<span class="opt-n">&times;</span><span>Batalkan pilihan</span>';
batal.addEventListener('click', batalkanPilihan);
kotak.appendChild(batal);
tampilComposer(true);
scrollToBottom();
return;
}
submitMessage(b.getAttribute('data-value'));
});
});
}

function addMessage(content, isUser, hasOptions, options, questionType) {
const d = document.createElement('div');
d.className = 'msg ' + (isUser ? 'me' : 'ai');
const now = new Date();
const t = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
if (!isUser) d.setAttribute('data-ai', '');
d.innerHTML = '<div class="msg-in">' +
(isUser ? '' : '<div class="ai-head"><span class="ai-av"><img src="{{ asset('img/logo-inaai.webp') }}" alt="INaAI"></span>INaAI Agent</div>') +
'<div class="bub">' + escapeHtml(content) + '<div class="bub-t">' + t + '</div></div></div>';
messageList.appendChild(d);
if (!isUser) showOptions(hasOptions ? (options || []) : [], questionType);
scrollToBottom();
}

function showTyping() {
const d = document.createElement('div');
d.className = 'msg ai';
d.id = 'typingRow';
d.innerHTML = '<div class="msg-in"><div class="bub"><span class="typing"><i></i><i></i><i></i></span></div></div>';
messageList.appendChild(d);
scrollToBottom();
}

function hideTyping() {
const t = document.getElementById('typingRow');
if (t) t.remove();
}

async function submitMessage(message) {
if (messageInput && messageInput.disabled) return;
bersihkanError();

if (!message) {
tampilError('Jawaban wajib diisi.');
if (messageInput) messageInput.focus();
return;
}
if (!jawabanBermakna(message)) {
tampilError('Jawaban belum cukup jelas. Silakan tulis jawaban yang relevan.');
if (messageInput) messageInput.focus();
return;
}

messageInput.disabled = true;
sendButton.disabled = true;
hapusOpsi();
addMessage(message, true);
messageInput.value = '';
autoGrow();
syncSendButton();
showTyping();

try {
// Percakapan baru belum punya baris di database — pesan pertama yang membuatnya.
const res = await fetch(conversationId ? urlKirim() : urlMulai, {
method: 'POST',
headers: {
'Content-Type': 'application/json',
'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
'Accept': 'application/json',
'X-Requested-With': 'XMLHttpRequest'
},
body: JSON.stringify({ message: message })
});
const data = await res.json();
hideTyping();

if (data.conversation_id && !conversationId) {
conversationId = data.conversation_id;
history.replaceState({}, '', data.conversation_url);
// Daftar riwayat di sidebar sekarang usang.
window.dispatchEvent(new Event('inaai:riwayat-usang'));
}

// Balasan validasi juga berupa pesan AI lengkap dengan pilihan sebelumnya.
if (data.ai_response && data.ai_response.content) {
addMessage(
data.ai_response.content,
false,
!!data.ai_response.has_options,
data.ai_response.options || [],
data.ai_response.question_type || null
);
if (data.selesai) { rayakan(); return; }
} else {
tampilError(data.message || 'Terjadi kesalahan saat memproses permintaan Anda.');
}
} catch (e) {
hideTyping();
tampilError('Koneksi bermasalah. Silakan coba lagi.');
}

messageInput.disabled = false;
syncSendButton();
messageInput.focus();
}

/**
 * Sesi selesai: konfeti singkat, kotak tulis diganti tombol Chat Baru.
 */
function rayakan() {
hapusOpsi();
var zona = document.querySelector('.composer-zone .chat-col');
if (zona) {
zona.innerHTML =
'<div class="selesai-box">' +
'<div class="selesai-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg></div>' +
'<div class="selesai-t">Catatan pekerjaan tersimpan</div>' +
'<div class="selesai-s">Sesi ini sudah ditutup. Hasilnya bisa dilihat di Pekerjaan Saya.</div>' +
'<div class="selesai-aksi">' +
'<a class="btn" href="{{ route('pekerjaan.index') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18M9 16l2 2 4-4"/></svg>Pekerjaan Saya</a>' +
'<a class="btn btn-primary" href="{{ route('chat.baru') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>Chat Baru</a>' +
'</div></div>';
}
konfeti();
if (window.InaaiToast) window.InaaiToast.sukses('Catatan pekerjaan berhasil disimpan.', { judul: 'Sesi Selesai' });
window.dispatchEvent(new Event('inaai:riwayat-usang'));
scrollToBottom();
}

function konfeti() {
var warna = ['#f55d14', '#1d4ed8', '#047857', '#7e22ce', '#be123c', '#b45309'];
var lapis = document.createElement('div');
lapis.className = 'konfeti';
for (var i = 0; i < 70; i++) {
var k = document.createElement('i');
k.style.left = (Math.random() * 100) + '%';
k.style.background = warna[i % warna.length];
k.style.animationDelay = (Math.random() * 0.5) + 's';
k.style.animationDuration = (2.4 + Math.random() * 1.6) + 's';
k.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
lapis.appendChild(k);
}
document.body.appendChild(lapis);
setTimeout(function () { lapis.remove(); }, 4600);
}

function autoGrow() {
messageInput.style.height = 'auto';
messageInput.style.height = Math.min(messageInput.scrollHeight, 210) + 'px';
}

function syncSendButton() {
if (!sendButton) return;
sendButton.disabled = messageInput.disabled || messageInput.value.trim() === '';
}

function renderLampiran() {
attList.innerHTML = lampiran.map(function (f, i) {
return '<span class="att">' + escapeHtml(f.name) + '<button type="button" data-i="' + i + '">✕</button></span>';
}).join('');
attList.querySelectorAll('button').forEach(function (b) {
b.addEventListener('click', function () {
lampiran.splice(parseInt(b.dataset.i, 10), 1);
renderLampiran();
});
});
}

if (messageInput) {
messageInput.addEventListener('input', function () { autoGrow(); syncSendButton(); bersihkanError(); });
messageInput.addEventListener('keydown', function (e) {
if (e.key === 'Enter' && !e.shiftKey) {
e.preventDefault();
submitMessage(messageInput.value.trim());
}
});
messageInput.focus();
syncSendButton();
}

if (messageForm) {
messageForm.addEventListener('submit', function (e) {
e.preventDefault();
submitMessage(messageInput.value.trim());
});
}

if (attachBtn) {
attachBtn.addEventListener('click', function () { attachInput.click(); });
attachInput.addEventListener('change', function () {
Array.prototype.forEach.call(attachInput.files, function (f) { lampiran.push(f); });
attachInput.value = '';
renderLampiran();
});
}

if (voiceBtn) {
let rec = null;
const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
voiceBtn.addEventListener('click', function () {
if (!SR) {
alert('Browser ini belum mendukung input suara. Gunakan Chrome atau Safari terbaru.');
return;
}
if (rec) { rec.stop(); return; }
rec = new SR();
rec.lang = 'id-ID';
rec.interimResults = false;
voiceBtn.classList.add('rec');
rec.onresult = function (e) {
const teks = e.results[0][0].transcript;
messageInput.value = (messageInput.value ? messageInput.value + ' ' : '') + teks;
autoGrow();
syncSendButton();
};
rec.onend = function () { voiceBtn.classList.remove('rec'); rec = null; messageInput.focus(); };
rec.onerror = function () { voiceBtn.classList.remove('rec'); rec = null; };
rec.start();
});
}

/* Pilihan untuk pesan AI terakhir yang dirender server. */
showOptions(opsiAwal.options || [], opsiAwal.question_type || null);
// Blok pilihan menambah tinggi composer, jadi posisi gulir perlu disetel ulang.
scrollToBottom();
</script>
@endpush
