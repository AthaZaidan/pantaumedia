@extends('layouts.app')

@section('title', 'Chatbot — SOSPEM')

@section('styles')
<style>
    /* Full-bleed layout halaman chat menyatu dengan canvas tanpa kotak di tengah */
    .main-workspace {
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
        gap: 0 !important;
    }

    .chatbot-shell {
        background: var(--color-canvas);
        border: none;
        border-radius: 0;
        box-shadow: none;
        display: flex;
        flex: 1;
        width: 100%;
        height: 100%;
        min-height: 0;
        overflow: hidden;
        position: relative;
    }

    /* Sidebar Riwayat Chat di sebelah kiri area chat */
    .chat-history-sidebar {
        width: 260px;
        flex-shrink: 0;
        border-right: 1px solid var(--color-border);
        background: var(--color-surface);
        display: flex;
        flex-direction: column;
        padding: 14px 12px;
        gap: 12px;
        min-height: 0;
    }

    .chat-history-top {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chat-drawer-close-btn {
        display: none;
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        border-radius: var(--radius-sm);
        border: 1px solid var(--color-border);
        background: var(--color-surface-subtle);
        color: var(--color-text-primary);
        font-size: 18px;
        line-height: 1;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .chat-history-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(2px);
        z-index: 1070;
    }
    .chat-history-backdrop.is-open {
        display: block;
    }

    .new-chat-btn {
        width: 100%;
        padding: 10px 14px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--color-teal-border);
        background: var(--color-teal-bg);
        color: var(--color-teal-dark);
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .new-chat-btn:hover {
        background: var(--color-teal);
        border-color: var(--color-teal);
        color: #ffffff;
    }

    .chat-history-heading {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--color-text-muted);
        padding: 4px 6px 0;
    }

    .chat-history-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .chat-history-empty {
        font-size: 12px;
        color: var(--color-text-muted);
        padding: 12px 8px;
    }

    .chat-history-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        padding: 8px 10px;
        border-radius: var(--radius-sm);
        border: 1px solid transparent;
        background: transparent;
        color: var(--color-text-secondary);
        font-size: 12.5px;
        cursor: pointer;
        text-align: left;
        transition: background 0.12s ease, color 0.12s ease, border-color 0.12s ease;
        width: 100%;
    }
    .chat-history-item:hover {
        background: var(--color-surface-hover);
        color: var(--color-text-primary);
    }
    .chat-history-item.is-active {
        background: var(--color-surface-subtle);
        border-color: var(--color-border);
        color: var(--color-text-primary);
        font-weight: 600;
    }

    .chat-history-title {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-history-delete {
        opacity: 0;
        border: none;
        background: transparent;
        color: var(--color-text-muted);
        font-size: 15px;
        line-height: 1;
        width: 22px;
        height: 22px;
        border-radius: var(--radius-xs);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: opacity 0.12s ease, color 0.12s ease, background 0.12s ease;
    }
    .chat-history-item:hover .chat-history-delete,
    .chat-history-item.is-active .chat-history-delete {
        opacity: 1;
    }
    .chat-history-delete:hover {
        color: var(--color-red-dark);
        background: var(--color-red-bg);
    }

    /* Area Utama Chat */
    .chat-main-panel {
        flex: 1;
        min-width: 0;
        min-height: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: var(--color-canvas);
    }

    .chat-mobile-bar {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 14px;
        border-bottom: 1px solid var(--color-border);
        background: var(--color-surface-subtle);
        flex-shrink: 0;
    }

    .chat-empty-state {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 24px;
        background: var(--color-canvas);
    }

    .chat-empty-title {
        font-size: 36px;
        font-weight: 600;
        letter-spacing: -0.02em;
        color: var(--color-text-primary);
        user-select: none;
    }

    .chat-messages-scroll {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-y: contain;
        padding: 24px 28px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        background: var(--color-canvas);
    }

    .chat-bubble-row {
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-width: min(82%, 780px);
    }

    .chat-bubble-row.is-user {
        align-self: flex-end;
        align-items: flex-end;
    }

    .chat-bubble-row.is-assistant {
        align-self: flex-start;
        align-items: flex-start;
    }

    .chat-sender-meta {
        font-size: 11.5px;
        color: var(--color-text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chat-bubble-box {
        padding: 12px 16px;
        border-radius: var(--radius-md);
        font-size: 13.5px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .chat-bubble-row.is-user .chat-bubble-box {
        background: var(--color-teal);
        color: #ffffff;
        border: 1px solid var(--color-teal);
    }

    .chat-bubble-row.is-assistant .chat-bubble-box {
        background: var(--color-surface);
        color: var(--color-text-primary);
        border: 1px solid var(--color-border);
    }

    .chat-bubble-row.is-error .chat-bubble-box {
        background: var(--color-red-bg);
        color: var(--color-red-dark);
        border: 1px solid var(--color-red-border);
    }

    .chat-input-bar {
        flex-shrink: 0;
        padding: 14px 28px;
        border-top: 1px solid var(--color-border);
        background: var(--color-surface);
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .chat-textarea {
        flex: 1;
        padding: 10px 14px;
        font-size: 13.5px;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        background: var(--color-surface-subtle);
        color: var(--color-text-primary);
        resize: none;
        min-height: 44px;
        max-height: 120px;
        line-height: 1.45;
        outline: none;
    }
    .chat-textarea:focus {
        border-color: var(--color-teal);
        background: var(--color-surface);
    }

    @media (max-width: 768px) {
        .mobile-topbar {
            border-radius: 0 !important;
            border-left: none !important;
            border-right: none !important;
            border-top: none !important;
            flex-shrink: 0;
        }

        .chat-mobile-bar {
            display: flex;
        }

        .chat-drawer-close-btn {
            display: inline-flex;
        }

        .chat-history-sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 270px;
            max-width: 82vw;
            max-height: none;
            border-right: 1px solid var(--color-border);
            border-bottom: none;
            padding: 14px 12px;
            z-index: 1080;
            transform: translateX(-100%);
            transition: transform 0.2s ease;
            box-shadow: 0 20px 44px rgba(0, 0, 0, 0.45);
        }

        .chat-history-sidebar.is-drawer-open {
            transform: translateX(0);
        }

        .chat-history-delete {
            opacity: 1;
        }

        .chat-messages-scroll {
            padding: 16px 14px;
            gap: 14px;
        }

        .chat-bubble-row {
            max-width: 92%;
        }

        .chat-input-bar {
            padding: 10px 12px max(10px, env(safe-area-inset-bottom));
        }

        .chat-textarea {
            font-size: 14px;
        }
    }
</style>
@endsection

@section('content')
    <div class="chat-history-backdrop" id="chatHistoryBackdrop" onclick="closeChatHistoryDrawer()"></div>

    <section class="chatbot-shell">
        <aside class="chat-history-sidebar" id="chatHistorySidebar" aria-label="Riwayat Chat">
            <div class="chat-history-top">
                <button type="button" id="newChatBtn" class="new-chat-btn" onclick="createNewChat()">+ Chat Baru</button>
                <button type="button" class="chat-drawer-close-btn" onclick="closeChatHistoryDrawer()" aria-label="Tutup Riwayat Chat">&times;</button>
            </div>
            <div class="chat-history-heading">Riwayat Chat</div>
            <div class="chat-history-list" id="chatHistoryList"></div>
        </aside>

        <div class="chat-main-panel">
            <div class="chat-mobile-bar">
                <button type="button" class="btn-clean" onclick="toggleChatHistoryDrawer()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Riwayat Chat
                </button>
                <button type="button" class="btn-clean btn-primary" onclick="createNewChat()">+ Chat Baru</button>
            </div>

            <div id="chatEmptyState" class="chat-empty-state">
                <h1 class="chat-empty-title">Halo</h1>
            </div>

            <div class="chat-messages-scroll" id="chatMessages" style="display: none;"></div>

            <form class="chat-input-bar" onsubmit="handleChatSubmit(event)">
                <textarea id="chatInput" class="chat-textarea" rows="1" placeholder="Ketik pesan..." oninput="autoResizeTextarea(this)" onkeydown="handleTextareaKey(event)" required></textarea>
                <button type="submit" id="chatSendBtn" class="btn-clean btn-primary" style="height: 44px; padding: 0 18px; flex-shrink: 0;">Kirim</button>
            </form>
        </div>
    </section>
@endsection

@section('scripts')
<script>
    const STORAGE_KEY = 'sospem_chat_sessions_v2';
    let sessions = [];
    let activeSessionId = null;

    function cleanDisplayContent(text) {
        return String(text || '')
            .replace(/\*\*/g, '')
            .replace(/__/g, '')
            .replace(/`/g, '')
            .replace(/\*([^*\s][^*\n]*?[^*\s]|[^*\s])\*/g, '$1')
            .replace(/_([^_\s][^_\n]*?[^_\s]|[^_\s])_/g, '$1')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function getCurrentTimeLabel() {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        return `${hh}:${mm} WIB`;
    }

    function generateSessionId() {
        return 'chat_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7);
    }

    function buildTitleFromText(text) {
        const clean = String(text || '').replace(/\s+/g, ' ').trim();
        if (!clean) return 'Chat Baru';
        return clean.length > 32 ? clean.slice(0, 32) + '...' : clean;
    }

    function loadSessionsFromStorage() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            const parsed = raw ? JSON.parse(raw) : [];
            if (Array.isArray(parsed)) {
                sessions = parsed.filter(s => s && typeof s.id === 'string' && Array.isArray(s.messages));
            }
        } catch (e) {
            sessions = [];
        }

        // Mulai dengan sesi baru kosong ("Halo") ketika belum ada sesi sama sekali
        const emptyExisting = sessions.find(s => s.messages.length === 0);
        if (emptyExisting) {
            activeSessionId = emptyExisting.id;
        } else {
            const fresh = {
                id: generateSessionId(),
                title: 'Chat Baru',
                messages: [],
                updatedAt: Date.now()
            };
            sessions.unshift(fresh);
            activeSessionId = fresh.id;
            saveSessionsToStorage();
        }
    }

    function saveSessionsToStorage() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(sessions.slice(0, 40)));
        } catch (e) {
            // Abaikan bila storage penuh
        }
    }

    function getActiveSession() {
        return sessions.find(s => s.id === activeSessionId) || null;
    }

    function toggleChatHistoryDrawer() {
        const sidebar = document.getElementById('chatHistorySidebar');
        const backdrop = document.getElementById('chatHistoryBackdrop');
        if (!sidebar || !backdrop) return;
        const willOpen = !sidebar.classList.contains('is-drawer-open');
        sidebar.classList.toggle('is-drawer-open', willOpen);
        backdrop.classList.toggle('is-open', willOpen);
    }

    function closeChatHistoryDrawer() {
        const sidebar = document.getElementById('chatHistorySidebar');
        const backdrop = document.getElementById('chatHistoryBackdrop');
        if (sidebar) sidebar.classList.remove('is-drawer-open');
        if (backdrop) backdrop.classList.remove('is-open');
    }

    function autoResizeTextarea(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    function createNewChat() {
        const existingEmpty = sessions.find(s => s.messages.length === 0);
        if (existingEmpty) {
            activeSessionId = existingEmpty.id;
        } else {
            const newSession = {
                id: generateSessionId(),
                title: 'Chat Baru',
                messages: [],
                updatedAt: Date.now()
            };
            sessions.unshift(newSession);
            activeSessionId = newSession.id;
            saveSessionsToStorage();
        }
        renderSidebarHistory();
        renderActiveChat();
        closeChatHistoryDrawer();
        document.getElementById('chatInput').focus();
    }

    function selectChatSession(sessionId) {
        activeSessionId = sessionId;
        renderSidebarHistory();
        renderActiveChat();
        closeChatHistoryDrawer();
        document.getElementById('chatInput').focus();
    }

    function deleteChatSession(event, sessionId) {
        event.stopPropagation();
        sessions = sessions.filter(s => s.id !== sessionId);
        if (sessions.length === 0 || activeSessionId === sessionId) {
            if (sessions.length === 0) {
                const fresh = {
                    id: generateSessionId(),
                    title: 'Chat Baru',
                    messages: [],
                    updatedAt: Date.now()
                };
                sessions.unshift(fresh);
                activeSessionId = fresh.id;
            } else {
                activeSessionId = sessions[0].id;
            }
        }
        saveSessionsToStorage();
        renderSidebarHistory();
        renderActiveChat();
    }

    function renderSidebarHistory() {
        const listEl = document.getElementById('chatHistoryList');
        listEl.innerHTML = '';

        const nonEmptyOrActive = sessions.filter(s => s.messages.length > 0 || s.id === activeSessionId);
        if (nonEmptyOrActive.length === 0) {
            const emptyMsg = document.createElement('div');
            emptyMsg.className = 'chat-history-empty';
            emptyMsg.textContent = 'Belum ada riwayat chat.';
            listEl.appendChild(emptyMsg);
            return;
        }

        nonEmptyOrActive.forEach(session => {
            const row = document.createElement('div');
            row.className = 'chat-history-item' + (session.id === activeSessionId ? ' is-active' : '');
            row.setAttribute('role', 'button');
            row.setAttribute('tabindex', '0');
            row.onclick = () => selectChatSession(session.id);
            row.onkeydown = (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectChatSession(session.id);
                }
            };

            const titleSpan = document.createElement('span');
            titleSpan.className = 'chat-history-title';
            titleSpan.textContent = session.title || 'Chat Baru';
            titleSpan.title = session.title || 'Chat Baru';

            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'chat-history-delete';
            delBtn.title = 'Hapus chat ini';
            delBtn.setAttribute('aria-label', 'Hapus chat ini');
            delBtn.textContent = '×';
            delBtn.onclick = (e) => deleteChatSession(e, session.id);

            row.appendChild(titleSpan);
            row.appendChild(delBtn);
            listEl.appendChild(row);
        });
    }

    function renderActiveChat() {
        const session = getActiveSession();
        const emptyStateEl = document.getElementById('chatEmptyState');
        const messagesEl = document.getElementById('chatMessages');
        messagesEl.innerHTML = '';

        if (!session || session.messages.length === 0) {
            emptyStateEl.style.display = 'flex';
            messagesEl.style.display = 'none';
            return;
        }

        emptyStateEl.style.display = 'none';
        messagesEl.style.display = 'flex';

        session.messages.forEach(msg => {
            appendBubbleDom(msg.role, msg.content, msg.time || '', Boolean(msg.isError));
        });
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function appendBubbleDom(role, content, metaLabel, isError = false) {
        const container = document.getElementById('chatMessages');
        const emptyStateEl = document.getElementById('chatEmptyState');
        emptyStateEl.style.display = 'none';
        container.style.display = 'flex';

        const row = document.createElement('div');
        row.className = `chat-bubble-row is-${role}${isError ? ' is-error' : ''}`;

        const meta = document.createElement('div');
        meta.className = 'chat-sender-meta';
        if (role === 'user') {
            meta.innerHTML = `<span style="font-weight: 600; color: var(--color-text-primary);">Anda</span><span>&middot;</span><span>${metaLabel || getCurrentTimeLabel()}</span>`;
        } else {
            meta.innerHTML = `<span style="font-weight: 600; color: ${isError ? 'var(--color-red-dark)' : 'var(--color-teal-dark)'};">Chatbot SOSPEM</span><span>&middot;</span><span>${metaLabel || getCurrentTimeLabel()}</span>`;
        }

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble-box';
        bubble.textContent = cleanDisplayContent(content);

        row.appendChild(meta);
        row.appendChild(bubble);
        container.appendChild(row);
        container.scrollTop = container.scrollHeight;
        return row;
    }

    function handleTextareaKey(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            handleChatSubmit(event);
        }
    }

    function handleChatSubmit(event) {
        event.preventDefault();
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        input.style.height = 'auto';
        submitQuestion(text);
    }

    async function submitQuestion(text) {
        const sendBtn = document.getElementById('chatSendBtn');
        const session = getActiveSession();
        if (!session) return;

        const nowLabel = getCurrentTimeLabel();

        if (session.messages.length === 0) {
            session.title = buildTitleFromText(text);
        }

        session.messages.push({ role: 'user', content: text, time: nowLabel, isError: false });
        session.updatedAt = Date.now();
        saveSessionsToStorage();
        renderSidebarHistory();

        appendBubbleDom('user', text, nowLabel);
        const loadingRow = appendBubbleDom('assistant', 'Sedang memproses pertanyaan...', 'Mengetik');
        sendBtn.disabled = true;

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 45000);

        // Ambil 6 pesan terakhir sebelumnya sebagai konteks percakapan
        const historyPayload = session.messages
            .slice(0, -1)
            .filter(m => !m.isError)
            .slice(-6)
            .map(m => ({ role: m.role, content: m.content }));

        try {
            const res = await fetch("{{ route('api.chatbot.ask') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    message: text,
                    issue_slug: 'all',
                    history: historyPayload
                }),
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            let data = null;
            try {
                data = await res.json();
            } catch (parseErr) {
                data = null;
            }

            loadingRow.remove();

            const replyTime = getCurrentTimeLabel();

            if (!res.ok) {
                const errMsg = (data && data.reply)
                    ? data.reply
                    : `Permintaan tidak dapat diproses (HTTP ${res.status}). Silakan coba lagi.`;
                session.messages.push({ role: 'assistant', content: errMsg, time: replyTime, isError: true });
                saveSessionsToStorage();
                appendBubbleDom('assistant', errMsg, replyTime, true);
                return;
            }

            const reply = (data && data.reply) ? data.reply : 'Maaf, belum ada jawaban yang dapat ditampilkan.';
            const isErr = Boolean(data && data.status === 'error');
            session.messages.push({ role: 'assistant', content: reply, time: replyTime, isError: isErr });
            session.updatedAt = Date.now();
            saveSessionsToStorage();
            appendBubbleDom('assistant', reply, replyTime, isErr);
        } catch (err) {
            clearTimeout(timeoutId);
            loadingRow.remove();
            const replyTime = getCurrentTimeLabel();
            const message = err && err.name === 'AbortError'
                ? 'Waktu tunggu habis (lebih dari 45 detik). Silakan kirim ulang pertanyaan Anda.'
                : 'Tidak dapat terhubung ke server. Periksa apakah server lokal masih aktif lalu coba lagi.';
            session.messages.push({ role: 'assistant', content: message, time: replyTime, isError: true });
            saveSessionsToStorage();
            appendBubbleDom('assistant', message, replyTime, true);
        } finally {
            sendBtn.disabled = false;
            document.getElementById('chatInput').focus();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadSessionsFromStorage();
        renderSidebarHistory();
        renderActiveChat();
    });
</script>
@endsection
