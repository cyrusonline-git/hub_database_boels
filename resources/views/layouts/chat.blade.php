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
    .chat-msg { max-width: 78%; padding: 8px 12px; border-radius: 14px; font-size: 14px; white-space: pre-wrap; word-break: break-word; }
    .chat-msg.mine { align-self: flex-end; background: var(--boels-orange, #FF6600); color: #fff; border-bottom-right-radius: 4px; }
    .chat-msg.theirs { align-self: flex-start; background: #fff; border: 1px solid #e5e5e5; border-bottom-left-radius: 4px; }
    .chat-msg .tm { display: block; font-size: 10px; opacity: .7; margin-top: 3px; text-align: right; }
    .chat-input { flex: 0 0 auto; display: flex; gap: 8px; padding: 10px; background: #fff; border-top: 1px solid #eee; }
    .chat-input textarea {
        flex: 1 1 auto; resize: none; border: 1px solid #ddd; border-radius: 10px;
        padding: 8px 10px; font-size: 14px; height: 42px; outline: none;
    }
    .chat-input button {
        flex: 0 0 auto; width: 42px; height: 42px; border-radius: 10px; border: none;
        background: var(--boels-orange, #FF6600); color: #fff; font-size: 17px;
    }
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
    <div class="chat-input" id="chatInputWrap" style="display:none;">
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

    function setBadge(n) {
        badge.textContent = n > 99 ? '99+' : n;
        badge.style.display = n > 0 ? 'inline-flex' : 'none';
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
                '<div class="chat-msg ' + (m.mine ? 'mine' : 'theirs') + '">' + esc(m.body)
                + '<span class="tm">' + esc(m.time) + (m.mine && m.read ? ' ✓✓' : '') + '</span></div>'
            ).join('') + '</div>';
            if (html !== lastRender) {
                var atBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 60;
                lastRender = html;
                body.innerHTML = html;
                if (scroll || atBottom) body.scrollTop = body.scrollHeight;
            }
            pollUnreadSoon();
        }).catch(() => {});
    }
    var unreadSoonTimer = null;
    function pollUnreadSoon() {
        clearTimeout(unreadSoonTimer);
        unreadSoonTimer = setTimeout(pollUnread, 400);
    }

    function send() {
        var msg = text.value.trim();
        if (!msg || !currentContact) return;
        text.value = '';
        fetch('{{ route('chat.send') }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
            body: JSON.stringify({recipient_id: currentContact.id, body: msg}),
        }).then(() => loadThread(true)).catch(() => {});
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
