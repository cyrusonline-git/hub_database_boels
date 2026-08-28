{{-- Interne chat: zwevend wolkje rechtsonder + paneel. Polling, geen websockets. --}}
<style>
    #chatFab {
        position: fixed; right: 24px; bottom: 24px; z-index: 1060;
        width: 58px; height: 58px; border-radius: 50%;
        background: var(--boels-orange, #FF6600); color: #fff; border: none;
        box-shadow: 0 6px 18px rgba(0,0,0,.25);
        font-size: 26px; display: flex; align-items: center; justify-content: center;
        transition: transform .15s ease;
    }
    #chatFab:hover { transform: scale(1.07); }
    #chatBadge {
        position: absolute; top: -4px; right: -4px; min-width: 22px; height: 22px;
        border-radius: 11px; background: #dc3545; color: #fff;
        font-size: 12px; font-weight: 700; display: none;
        align-items: center; justify-content: center; padding: 0 6px;
        border: 2px solid #fff;
    }
    #chatPanel {
        position: fixed; right: 24px; bottom: 96px; z-index: 1060;
        width: 360px; max-width: calc(100vw - 32px); height: 480px; max-height: 70vh;
        background: #fff; border-radius: 14px; overflow: hidden; display: none;
        box-shadow: 0 12px 40px rgba(0,0,0,.28);
        flex-direction: column;
    }
    #chatPanel.open { display: flex; }
    .chat-head {
        background: var(--boels-orange, #FF6600); color: #fff; padding: 12px 14px;
        display: flex; align-items: center; gap: 10px; flex: 0 0 auto;
    }
    .chat-head .btn-back { background: none; border: none; color: #fff; font-size: 18px; padding: 0 4px; }
    .chat-body { flex: 1 1 auto; overflow-y: auto; background: #f6f7f9; }
    .chat-contact {
        display: flex; align-items: center; gap: 10px; padding: 10px 14px;
        cursor: pointer; background: #fff; border-bottom: 1px solid #eee;
    }
    .chat-contact:hover { background: #fff4ec; }
    .chat-avatar {
        width: 38px; height: 38px; border-radius: 50%; flex: 0 0 auto;
        background: #ffe3cf; color: #c65200; font-weight: 700; font-size: 14px;
        display: flex; align-items: center; justify-content: center;
    }
    .chat-contact .meta { flex: 1 1 auto; min-width: 0; }
    .chat-contact .meta .nm { font-weight: 600; font-size: 14px; }
    .chat-contact .meta .snip { font-size: 12px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-contact .right { text-align: right; flex: 0 0 auto; }
    .chat-contact .right .tm { font-size: 11px; color: #aaa; }
    .chat-contact .right .ub {
        display: inline-flex; min-width: 20px; height: 20px; border-radius: 10px;
        background: #dc3545; color: #fff; font-size: 11px; font-weight: 700;
        align-items: center; justify-content: center; padding: 0 6px;
    }
    .chat-msgs { padding: 12px; display: flex; flex-direction: column; gap: 8px; }
    .chat-msg { position: relative; max-width: 78%; padding: 8px 12px; border-radius: 14px; font-size: 14px; white-space: pre-wrap; word-break: break-word; }
    .chat-msg.mine { align-self: flex-end; background: var(--boels-orange, #FF6600); color: #fff; border-bottom-right-radius: 4px; }
    .chat-msg.theirs { align-self: flex-start; background: #fff; border: 1px solid #e5e5e5; border-bottom-left-radius: 4px; }
    .chat-msg .tm { display: block; font-size: 10px; opacity: .7; margin-top: 3px; text-align: right; }
    .chat-msg img.chat-photo { max-width: 100%; border-radius: 8px; display: block; margin-bottom: 4px; cursor: pointer; }
    .chat-msg .msg-del {
        position: absolute; top: -8px; left: -8px; width: 22px; height: 22px;
        border-radius: 50%; border: none; background: #dc3545; color: #fff;
        font-size: 11px; line-height: 1; display: none;
        align-items: center; justify-content: center; box-shadow: 0 1px 4px rgba(0,0,0,.3);
    }
    .chat-msg.mine:hover .msg-del, .chat-msg.mine.show-del .msg-del { display: inline-flex; }
    .chat-input { flex: 0 0 auto; display: flex; gap: 6px; padding: 10px; background: #fff; border-top: 1px solid #eee; align-items: flex-end; }
    .chat-input textarea {
        flex: 1 1 auto; resize: none; border: 1px solid #ddd; border-radius: 10px;
        padding: 8px 10px; font-size: 14px; height: 42px; outline: none;
    }
    .chat-input button {
        flex: 0 0 auto; width: 42px; height: 42px; border-radius: 10px; border: none;
        background: var(--boels-orange, #FF6600); color: #fff; font-size: 17px;
    }
    .chat-input button.btn-tool { background: #f1f2f4; color: #555; font-size: 19px; }
    #chatEmojiPanel {
        position: absolute; bottom: 62px; left: 8px; right: 8px; z-index: 5;
        background: #fff; border: 1px solid #e5e5e5; border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,.18); padding: 8px; display: none;
        grid-template-columns: repeat(8, 1fr); gap: 2px; max-height: 180px; overflow-y: auto;
    }
    #chatEmojiPanel.open { display: grid; }
    #chatEmojiPanel button {
        border: none; background: none; font-size: 22px; padding: 4px; border-radius: 8px; cursor: pointer;
    }
    #chatEmojiPanel button:hover { background: #fff4ec; }
    #chatAttachPreview {
        display: none; align-items: center; gap: 8px; padding: 6px 12px;
        background: #fff8f2; border-top: 1px solid #ffe3cf; font-size: 12px; flex: 0 0 auto;
    }
    #chatAttachPreview img { height: 34px; border-radius: 6px; }
    #chatAttachPreview .rm { border: none; background: none; color: #dc3545; font-size: 15px; margin-left: auto; }
    .chat-search { padding: 10px 12px; background: #fff; border-bottom: 1px solid #eee; flex: 0 0 auto; }
    .chat-search input { width: 100%; border: 1px solid #ddd; border-radius: 10px; padding: 7px 12px; font-size: 13px; outline: none; }
</style>

<button id="chatFab" type="button" title="Chat met collega's">
    <i class="bi bi-chat-dots-fill"></i>
    <span id="chatBadge">0</span>
</button>

<div id="chatPanel">
    <div class="chat-head">
        <button class="btn-back" id="chatBack" style="display:none;"><i class="bi bi-arrow-left"></i></button>
        <i class="bi bi-chat-dots-fill" id="chatHeadIcon"></i>
        <strong id="chatTitle" class="flex-grow-1 text-truncate">Chat</strong>
        <button class="btn-back" id="chatClose"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="chat-search" id="chatSearchWrap">
        <input type="text" id="chatSearch" placeholder="Zoek collega...">
    </div>
    <div class="chat-body" id="chatBody"></div>
    <div id="chatEmojiPanel"></div>
    <div id="chatAttachPreview">
        <img id="chatAttachThumb" alt="">
        <span>Foto klaar om te versturen</span>
        <button type="button" class="rm" id="chatAttachRemove" title="Foto verwijderen"><i class="bi bi-x-circle-fill"></i></button>
    </div>
    <div class="chat-input" id="chatInputWrap" style="display:none;">
        <button type="button" class="btn-tool" id="chatEmojiBtn" title="Emoji"><i class="bi bi-emoji-smile"></i></button>
        <button type="button" class="btn-tool" id="chatAttachBtn" title="Foto meesturen"><i class="bi bi-image"></i></button>
        <input type="file" id="chatFile" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
        <textarea id="chatText" placeholder="Typ een bericht..." maxlength="2000"></textarea>
        <button id="chatSend" title="Versturen"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var fab = document.getElementById('chatFab');
    var badge = document.getElementById('chatBadge');
    var panel = document.getElementById('chatPanel');
    var body = document.getElementById('chatBody');
    var title = document.getElementById('chatTitle');
    var back = document.getElementById('chatBack');
    var inputWrap = document.getElementById('chatInputWrap');
    var searchWrap = document.getElementById('chatSearchWrap');
    var text = document.getElementById('chatText');
    var search = document.getElementById('chatSearch');

    var open = false;
    var currentContact = null;   // {id, name}
    var contacts = [];
    var threadTimer = null, lastRender = '';

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }
    function get(url) { return fetch(url, {headers: {'Accept': 'application/json'}}).then(r => r.json()); }

    // --- Notificatiegeluid (zacht "pling" via Web Audio, geen bestand nodig)
    var audioCtx = null, lastCount = null;
    function ensureAudio() {
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
        } catch (e) {}
    }
    // Browsers staan geluid pas toe na een eerste klik/toets — dan alvast activeren
    ['pointerdown', 'keydown'].forEach(ev =>
        document.addEventListener(ev, ensureAudio, { once: true, passive: true }));
    function playDing() {
        if (!audioCtx || audioCtx.state !== 'running') return;
        try {
            [880, 1174.7].forEach(function (freq, i) {
                var o = audioCtx.createOscillator();
                var g = audioCtx.createGain();
                var t = audioCtx.currentTime + i * 0.09;
                o.type = 'sine'; o.frequency.value = freq;
                g.gain.setValueAtTime(0.0001, t);
                g.gain.exponentialRampToValueAtTime(0.12, t + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
                o.connect(g); g.connect(audioCtx.destination);
                o.start(t); o.stop(t + 0.4);
            });
        } catch (e) {}
    }

    function setBadge(n) {
        badge.textContent = n > 99 ? '99+' : n;
        badge.style.display = n > 0 ? 'inline-flex' : 'none';
        // Pling alleen als er écht iets nieuws bijkomt (niet bij laden van de pagina)
        if (lastCount !== null && n > lastCount) playDing();
        lastCount = n;
    }
    function pollUnread() {
        get('{{ route('chat.unread') }}').then(d => {
            setBadge(d.count);
            // Nieuw bericht terwijl de lijst openstaat? Lijst verversen.
            if (open && !currentContact) loadContacts();
        }).catch(() => {});
    }

    function showContacts() {
        currentContact = null;
        clearInterval(threadTimer);
        title.textContent = 'Chat — collega’s';
        back.style.display = 'none';
        inputWrap.style.display = 'none';
        searchWrap.style.display = '';
        loadContacts();
    }
    function loadContacts() {
        get('{{ route('chat.contacts') }}').then(d => {
            contacts = d.contacts;
            renderContacts();
        }).catch(() => {});
    }
    function renderContacts() {
        var q = (search.value || '').toLowerCase();
        var list = contacts.filter(c => !q || c.name.toLowerCase().includes(q));
        if (!list.length) {
            body.innerHTML = '<div class="text-center text-muted p-4 small">Geen collega’s gevonden.</div>';
            return;
        }
        body.innerHTML = list.map(c =>
            '<div class="chat-contact" data-id="' + c.id + '" data-name="' + esc(c.name) + '">'
            + '<div class="chat-avatar">' + esc(c.initials) + '</div>'
            + '<div class="meta"><div class="nm">' + esc(c.name) + '</div>'
            + '<div class="snip">' + (c.last_body ? (c.last_mine ? 'Jij: ' : '') + esc(c.last_body) : '<i>Nog geen berichten</i>') + '</div></div>'
            + '<div class="right">' + (c.last_at ? '<div class="tm">' + esc(c.last_at) + '</div>' : '')
            + (c.unread > 0 ? '<span class="ub">' + c.unread + '</span>' : '') + '</div></div>'
        ).join('');
        body.querySelectorAll('.chat-contact').forEach(el => {
            el.addEventListener('click', () => openThread(parseInt(el.dataset.id), el.dataset.name));
        });
    }

    function openThread(id, name) {
        currentContact = {id: id, name: name};
        title.textContent = name;
        back.style.display = '';
        searchWrap.style.display = 'none';
        inputWrap.style.display = 'flex';
        body.innerHTML = '<div class="text-center text-muted p-4 small">Laden...</div>';
        lastRender = '';
        loadThread(true);
        clearInterval(threadTimer);
        threadTimer = setInterval(() => loadThread(false), 5000);
        setTimeout(() => text.focus(), 50);
    }
    function loadThread(scroll) {
        if (!currentContact) return;
        get('/chat/thread/' + currentContact.id).then(d => {
            var html = '<div class="chat-msgs">' + d.messages.map(m =>
                '<div class="chat-msg ' + (m.mine ? 'mine' : 'theirs') + '">'
                + (m.mine ? '<button type="button" class="msg-del" data-id="' + m.id + '" title="Bericht verwijderen"><i class="bi bi-trash"></i></button>' : '')
                + (m.image ? '<img class="chat-photo" src="' + esc(m.image) + '" alt="Foto" loading="lazy">' : '')
                + esc(m.body)
                + '<span class="tm">' + esc(m.time) + (m.mine && m.read ? ' ✓✓' : '') + '</span></div>'
            ).join('') + '</div>';
            if (html !== lastRender) {
                var atBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 60;
                lastRender = html;
                body.innerHTML = html;
                if (scroll || atBottom) body.scrollTop = body.scrollHeight;
                // Foto aanklikken = groot openen in nieuw tabblad
                body.querySelectorAll('.chat-photo').forEach(img => {
                    img.addEventListener('click', () => window.open(img.src, '_blank'));
                });
                // Op telefoons: tik op je eigen bericht om het verwijder-knopje te tonen
                body.querySelectorAll('.chat-msg.mine').forEach(el => {
                    el.addEventListener('click', function (e) {
                        if (e.target.closest('.msg-del') || e.target.classList.contains('chat-photo')) return;
                        this.classList.toggle('show-del');
                    });
                });
                // Eigen bericht verwijderen
                body.querySelectorAll('.msg-del').forEach(btn => {
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        if (!confirm('Dit bericht verwijderen? Het verdwijnt ook bij de ontvanger.')) return;
                        fetch('/chat/delete/' + this.dataset.id, {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                        }).then(() => { lastRender = ''; loadThread(false); }).catch(() => {});
                    });
                });
            }
            pollUnreadSoon();
        }).catch(() => {});
    }
    var unreadSoonTimer = null;
    function pollUnreadSoon() {
        clearTimeout(unreadSoonTimer);
        unreadSoonTimer = setTimeout(pollUnread, 400);
    }

    // --- Foto meesturen
    var attachFile = null;
    var fileInput = document.getElementById('chatFile');
    var attachPreview = document.getElementById('chatAttachPreview');
    var attachThumb = document.getElementById('chatAttachThumb');

    document.getElementById('chatAttachBtn').addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', function () {
        var f = this.files[0];
        if (!f) return;
        if (f.size > 8 * 1024 * 1024) { alert('De foto is te groot (max. 8 MB).'); this.value = ''; return; }
        attachFile = f;
        attachThumb.src = URL.createObjectURL(f);
        attachPreview.style.display = 'flex';
    });
    document.getElementById('chatAttachRemove').addEventListener('click', clearAttach);
    function clearAttach() {
        attachFile = null;
        fileInput.value = '';
        attachPreview.style.display = 'none';
    }

    // --- Emoji-kiezer
    var emojiPanel = document.getElementById('chatEmojiPanel');
    var emojis = ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','🙂','😉','😍','😘','😎','🤔','🤨','😐','😴','🥳','😇',
                  '👍','👎','👌','🙏','💪','👏','🤝','✌️','🤞','👋','🔥','⭐','✅','❌','⚠️','❗','❓','💡','🎉','🎊',
                  '❤️','🧡','💚','💙','😢','😭','😤','😡','🤯','🤒','🚀','🚧','🔧','🔨','🏗️','🚜','📦','📸','📞','☕'];
    emojiPanel.innerHTML = emojis.map(e => '<button type="button">' + e + '</button>').join('');
    emojiPanel.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', function () {
            var start = text.selectionStart ?? text.value.length;
            var end = text.selectionEnd ?? text.value.length;
            text.value = text.value.slice(0, start) + this.textContent + text.value.slice(end);
            var pos = start + this.textContent.length;
            text.focus();
            text.setSelectionRange(pos, pos);
        });
    });
    document.getElementById('chatEmojiBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        emojiPanel.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
        if (!emojiPanel.contains(e.target) && e.target.id !== 'chatEmojiBtn') {
            emojiPanel.classList.remove('open');
        }
    });

    function send() {
        var msg = text.value.trim();
        if ((!msg && !attachFile) || !currentContact) return;
        emojiPanel.classList.remove('open');

        var fd = new FormData();
        fd.append('recipient_id', currentContact.id);
        if (msg) fd.append('body', msg);
        if (attachFile) fd.append('image', attachFile);
        text.value = '';
        clearAttach();

        fetch('{{ route('chat.send') }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
            body: fd,
        }).then(() => { lastRender = ''; loadThread(true); }).catch(() => {});
    }

    fab.addEventListener('click', function () {
        open = !open;
        panel.classList.toggle('open', open);
        if (open) showContacts();
        else { clearInterval(threadTimer); currentContact = null; }
    });
    document.getElementById('chatClose').addEventListener('click', function () {
        open = false; panel.classList.remove('open');
        clearInterval(threadTimer); currentContact = null;
    });
    back.addEventListener('click', showContacts);
    search.addEventListener('input', renderContacts);
    document.getElementById('chatSend').addEventListener('click', send);
    text.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });

    pollUnread();
    setInterval(pollUnread, 12000);
})();
</script>
