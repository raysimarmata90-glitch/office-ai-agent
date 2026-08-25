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
      data-confirm-ok="Hapus Percakapan"
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
.msg.ai .bub{background:transparent;border:none;padding:0;font-size:15px;line-height:1.72;color:var(--ink);max-width:100%}
.msg.me .bub{background:var(--primary-soft);color:var(--ink);border-radius:18px;border-bottom-right-radius:6px;padding:11px 16px;font-size:14.5px;line-height:1.65;max-width:min(560px,82%)}
.bub{white-space:pre-wrap;word-break:break-word}
.bub-t{font-size:10.5px;margin-top:6px;color:var(--muted3)}
.msg.me .bub-t{text-align:right}
.ai-head{display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:12px;color:var(--muted2);font-weight:600}
.ai-av{width:22px;height:22px;border-radius:7px;background:#fff;border:1px solid var(--line);display:grid;place-items:center;flex:none;padding:1px}
.ai-av img{width:100%;height:100%;object-fit:contain;display:block}

/* Pilihan cepat */
.opts{display:grid;gap:7px;margin-bottom:12px}
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
.prog{display:flex;align-items:center;gap:9px;font-size:11.5px;color:var(--muted2);margin-bottom:9px}
.typing{display:inline-flex;gap:4px;align-items:center}
.typing i{width:6px;height:6px;border-radius:50%;background:var(--muted3);display:block;animation:bl 1.3s infinite}
.typing i:nth-child(2){animation-delay:.18s}
.typing i:nth-child(3){animation-delay:.36s}
@keyframes bl{0%,60%,100%{opacity:.28}30%{opacity:1}}
.done-note{text-align:center;background:var(--done-bg);color:var(--done-fg);border-radius:11px;padding:12px;font-size:13px;max-width:760px;margin:0 auto}
.foot-note{text-align:center;font-size:11px;color:var(--muted3);margin:9px 0 4px}
@media(max-width:720px){.chat-col{padding:0 4px}.msg.ai .bub{font-size:14.5px}}
</style>
@endpush

@section('content')
<div class="chat-wrap">
<div class="msgs" id="messagesContainer">
<div class="chat-col" id="messageList">
@if($conversation)
@foreach($conversation->messages as $m)
<div class="msg {{ $m->sender_type === 'user' ? 'me' : 'ai' }}">
<div style="max-width:100%">
@if($m->sender_type !== 'user')
<div class="ai-head">
<span class="ai-av"><img src="{{ asset('img/logo-inaai.webp') }}" alt="INAai"></span>
INAai Agent
</div>
@endif
<div class="bub">{{ $m->content }}<div class="bub-t">{{ $m->created_at->format('H:i') }}</div></div>
</div>
</div>
@endforeach
@else
{{-- Layar "Chat Baru": pertanyaan pembuka ditampilkan saja, belum disimpan.
     Percakapan baru dibuat setelah user mengirim jawaban pertama. --}}
<div class="msg ai">
<div style="max-width:100%">
<div class="ai-head">
<span class="ai-av"><img src="{{ asset('img/logo-inaai.webp') }}" alt="INAai"></span>
INAai Agent
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
<div class="prog" id="questionProgress" style="display:none">
<span id="progressText">1 of 1</span>
<button type="button" class="btn btn-sm" id="skipButton">Skip</button>
</div>
<div class="opts" id="quickOptions" style="display:none"></div>
<form id="messageForm">
@csrf
<div class="composer">
<textarea id="messageInput" rows="1" placeholder="Tulis pesan untuk INAai Agent..."></textarea>
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
<div class="foot-note">INAai Agent dapat membuat kesalahan. Periksa kembali informasi penting.</div>
</div>
</div>
@else
<div class="done-note">Percakapan ini sudah selesai.</div>
@endif
</div>
@endsection

@push('script')
<script>
let conversationId = {{ $conversation?->id ?? 'null' }};
const urlMulai = @json(route('chat.mulai'));
const urlKirim = function () { return '/conversations/' + conversationId + '/messages'; };
const messagesContainer = document.getElementById('messagesContainer');
const messageList = document.getElementById('messageList');
const messageForm = document.getElementById('messageForm');
const messageInput = document.getElementById('messageInput');
const sendButton = document.getElementById('sendButton');
const quickOptions = document.getElementById('quickOptions');
const questionProgress = document.getElementById('questionProgress');
const progressText = document.getElementById('progressText');
const skipButton = document.getElementById('skipButton');
const attachBtn = document.getElementById('attachBtn');
const attachInput = document.getElementById('attachInput');
const attList = document.getElementById('attList');
const voiceBtn = document.getElementById('voiceBtn');

let currentQuestionIndex = 0;
let totalQuestions = 0;
let lampiran = [];

scrollToBottom();

function scrollToBottom() {
if (messagesContainer) messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function escapeHtml(value) {
return String(value).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
}

function optionsForQuestion(content) {
    const question = content.toLowerCase();

    if (/(jenis|tipe|kategori).*proposal|proposal.*(jenis|tipe|kategori)/i.test(question)) {
        return [
            'Business Proposal',
            'Project Proposal',
            'Research Proposal',
            'Event Proposal',
            'Partnership Proposal',
            'Internal Company Proposal'
        ];
    }

    if (/(tujuan|dibuat untuk|peruntukan|keperluan).*proposal|proposal.*(tujuan|dibuat untuk)/i.test(question)) {
        return [
            'Meminta approval',
            'Meminta budget',
            'Meminta resource',
            'Menawarkan solusi',
            'Menawarkan kerja sama',
            'Mengajukan proyek',
            'Mengajukan penelitian',
            'Melakukan improvement'
        ];
    }

    if (/(kompleksitas|tingkat kompleks|scope.*proposal)/i.test(question)) {
        return [
            'Simple — scope kecil, stakeholder terbatas, risiko rendah',
            'Medium — beberapa stakeholder, resource, timeline, dan risiko',
            'Complex — banyak stakeholder, dampak besar, budget signifikan'
        ];
    }

    if (/(primary audience|target audience|audience|pihak.*membaca|target.*pembaca|siapa.*membaca)/i.test(question)) {
        return [
            'Direksi / Executive',
            'Management / Manager',
            'Client',
            'Sponsor',
            'Investor',
            'Technical Team',
            'Internal Team',
            'Dosen / Akademik'
        ];
    }

    if (/(kedalaman|proposal depth|tingkat detail|level.*proposal)/i.test(question)) {
        return [
            'Executive Level — strategic value, business impact, investment',
            'Management Level — problem, solution, scope, timeline, budget',
            'Operational Level — detail requirement, implementation, technical scope'
        ];
    }

    if (/(background|konteks|latar belakang|situasi.*saat ini)/i.test(question) && !/(sudah|benar|sesuai)/i.test(question)) {
        return [
            'Ada masalah dalam proses bisnis saat ini',
            'Perlu digitalisasi sistem manual',
            'Ada opportunity untuk improvement',
            'Kebutuhan dari stakeholder',
            'Respon terhadap perubahan market'
        ];
    }

    if (/(masalah|problem|kendala|issue|tantangan).*(?:apa|yang)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
        return [
            'Proses manual memakan waktu lama',
            'Data tidak terintegrasi',
            'Biaya operasional tinggi',
            'Kualitas hasil tidak konsisten',
            'Tidak ada visibility real-time'
        ];
    }

    if (/(objektif|tujuan|goal|target.*capai|hasil.*ingin)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
        return [
            'Meningkatkan efisiensi operasional',
            'Mengurangi biaya operasional',
            'Meningkatkan kualitas output',
            'Mempercepat proses bisnis',
            'Meningkatkan customer satisfaction'
        ];
    }

    if (/(metodologi|approach|cara.*kerja|bagaimana.*dikerjakan)/i.test(question)) {
        return [
            'Agile / Scrum',
            'Waterfall',
            'Hybrid Approach',
            'Phased Implementation',
            'Proof of Concept dulu'
        ];
    }

    if (/(timeline|jadwal|waktu.*pengerjaan|berapa lama|durasi.*proyek)/i.test(question)) {
        return [
            '1-2 bulan',
            '3-4 bulan',
            '5-6 bulan',
            '6-12 bulan',
            'Lebih dari 1 tahun'
        ];
    }

    if (/(budget|anggaran|biaya|estimasi.*cost)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
        return [
            'Kurang dari Rp 50 juta',
            'Rp 50-100 juta',
            'Rp 100-500 juta',
            'Rp 500 juta - 1 miliar',
            'Lebih dari Rp 1 miliar'
        ];
    }

    if (/(resource|sumber daya|tim.*butuh|kebutuhan.*tim)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
        return [
            'Tim internal saja',
            'Tim internal + vendor',
            'Full outsource ke vendor',
            'Consultant + tim internal',
            'Mixed team (internal + external)'
        ];
    }

    if (/(deliverable|output|hasil.*konkret|yang.*dihasilkan)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
        return [
            'Software / Aplikasi',
            'Dokumen / Report',
            'Dashboard / Analytics',
            'System / Infrastructure',
            'Training / Knowledge Transfer'
        ];
    }

    if (/(risk|risiko|potential.*issue|tantangan.*proyek)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
        return [
            'Low Risk — minimal impact jika gagal',
            'Medium Risk — perlu mitigation plan',
            'High Risk — perlu executive oversight'
        ];
    }

    if (/(klasifikasi|audience|pemahaman|struktur|outline).*sudah (sesuai|benar)/i.test(question)) {
        return ['Ya, sudah sesuai', 'Belum sesuai, perlu revisi'];
    }

    if (/(sudah sesuai|sudah benar|apakah.*benar|konfirmasi|setuju.*dengan)/i.test(question)) {
        return ['Ya, sudah sesuai', 'Belum sesuai'];
    }

    if (/(format.*(?:keluaran|output|dokumen|proposal)|output.*format|bentuk.*dokumen)/i.test(question)) {
        return [
            'Markdown',
            'Microsoft Word (.docx)',
            'PowerPoint (.pptx)',
            'PDF'
        ];
    }

    if (/(prioritas|prioritas utama|fokus.*utama)/i.test(question) && !/format/i.test(question)) {
        return ['Ya, ini prioritas utama saya', 'Bukan prioritas utama'];
    }

    if (/(proyek lain|project.*lain|pekerjaan.*lain)/i.test(question)) {
        return ['Ya, ada proyek lain', 'Tidak, tidak ada proyek lain'];
    }

    if (/(estimasi|berapa lama|durasi|waktu.*selesai).*(?:task|pekerjaan|ini)/i.test(question) && !/(proyek|proposal)/i.test(question)) {
        return [
            '1-2 hari',
            '3-5 hari',
            '1-2 minggu',
            '2-4 minggu',
            'Lebih dari 1 bulan'
        ];
    }

    return [];
}

function conversationIsActive() {
return {{ ($conversation?->isActive() ?? true) ? 'true' : 'false' }};
}

function showOptions(content) {
if (!quickOptions || !conversationIsActive()) return;
const options = optionsForQuestion(content);
if (options.length === 0) {
quickOptions.innerHTML = '';
quickOptions.style.display = 'none';
questionProgress.style.display = 'none';
messageInput.focus();
return;
}
currentQuestionIndex++;
totalQuestions = Math.max(totalQuestions, currentQuestionIndex);
progressText.textContent = currentQuestionIndex + ' of ' + totalQuestions;
questionProgress.style.display = 'flex';
quickOptions.innerHTML = options.map((o, i) =>
'<button type="button" class="opt" data-value="' + escapeHtml(o) + '">' +
'<span class="opt-n">' + (i + 1) + '</span><span>' + escapeHtml(o) + '</span></button>'
).join('') +
'<button type="button" class="opt other" id="otherOption">' +
'<span class="opt-n">+</span><span>Jawaban lain</span></button>';
quickOptions.style.display = 'grid';
quickOptions.style.opacity = '1';
quickOptions.querySelectorAll('.opt').forEach(function (b) {
b.addEventListener('click', function () {
if (b.id === 'otherOption') {
// Seperti "Something else" di alur lama: pilihan tetap terlihat, hanya diredupkan.
quickOptions.style.opacity = '.5';
messageInput.focus();
return;
}
submitMessage(b.getAttribute('data-value'));
});
});
}

function addMessage(content, isUser) {
const d = document.createElement('div');
d.className = 'msg ' + (isUser ? 'me' : 'ai');
const now = new Date();
const t = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
d.innerHTML = '<div style="max-width:100%">' +
(isUser ? '' : '<div class="ai-head"><span class="ai-av"><img src="{{ asset('img/logo-inaai.webp') }}" alt="INAai"></span>INAai Agent</div>') +
'<div class="bub">' + escapeHtml(content) + '<div class="bub-t">' + t + '</div></div></div>';
messageList.appendChild(d);
if (!isUser) showOptions(content);
scrollToBottom();
}

function showTyping() {
const d = document.createElement('div');
d.className = 'msg ai';
d.id = 'typingRow';
d.innerHTML = '<div><div class="bub"><span class="typing"><i></i><i></i><i></i></span></div></div>';
messageList.appendChild(d);
scrollToBottom();
}

function hideTyping() {
const t = document.getElementById('typingRow');
if (t) t.remove();
}

async function submitMessage(message) {
if (!message) return;
messageInput.disabled = true;
sendButton.disabled = true;
if (skipButton) skipButton.disabled = true;
quickOptions.style.display = 'none';
quickOptions.style.opacity = '1';
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
if (data.success && data.ai_response) {
addMessage(data.ai_response.content, false);
} else {
addMessage('Maaf, terjadi kesalahan dalam memproses permintaan Anda.', false);
}
} catch (e) {
hideTyping();
addMessage('Koneksi bermasalah. Silakan coba lagi.', false);
}
messageInput.disabled = false;
if (skipButton) skipButton.disabled = false;
syncSendButton();
messageInput.focus();
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
messageInput.addEventListener('input', function () { autoGrow(); syncSendButton(); });
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

if (skipButton) {
skipButton.addEventListener('click', function () { submitMessage('Skip'); });
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
};
rec.onend = function () { voiceBtn.classList.remove('rec'); rec = null; messageInput.focus(); };
rec.onerror = function () { voiceBtn.classList.remove('rec'); rec = null; };
rec.start();
});
}

(function () {
const aiBubbles = messageList.querySelectorAll('.msg.ai .bub');
if (aiBubbles.length) showOptions(aiBubbles[aiBubbles.length - 1].textContent);
})();
</script>
@endpush
