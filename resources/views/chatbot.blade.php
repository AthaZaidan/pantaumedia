@extends('layouts.app')

@section('title', 'Chatbot — SOSPEM')

@section('styles')
<style>
    .chat-panel-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 76px);
        min-height: 580px;
        width: 100%;
        overflow: hidden;
    }

    .chat-messages-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 20px 24px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        background: var(--color-canvas);
    }

    .chat-bubble-row {
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-width: 82%;
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
        line-height: 1.55;
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

    .chat-input-bar {
        padding: 14px 20px;
        border-top: 1px solid var(--color-border);
        background: var(--color-surface);
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .chat-textarea {
        flex: 1;
        padding: 10px 14px;
        font-size: 13px;
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
</style>
@endsection

@section('content')
    <section class="chat-panel-card">
        <div class="chat-messages-scroll" id="chatMessages">
            <div class="chat-bubble-row is-assistant">
                <div class="chat-sender-meta">
                    <span style="font-weight: 600; color: var(--color-teal-dark);">Chatbot SOSPEM</span>
                    <span>&middot;</span>
                    <span>Siap</span>
                </div>
                <div class="chat-bubble-box">Halo! Silakan ajukan pertanyaan seputar data opini publik, sentimen lembaga pemerintahan, atau deteksi jaringan bot di SOSPEM.</div>
            </div>
        </div>

        <form class="chat-input-bar" onsubmit="handleChatSubmit(event)">
            <textarea id="chatInput" class="chat-textarea" rows="1" placeholder="Tulis pertanyaan Anda di sini..." onkeydown="handleTextareaKey(event)" required></textarea>
            <button type="submit" id="chatSendBtn" class="btn-clean btn-primary" style="height: 44px; padding: 0 18px;">Kirim</button>
        </form>
    </section>
@endsection

@section('scripts')
<script>
    const chatHistory = [];

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
        submitQuestion(text);
    }

    function appendChatMessage(role, content, metaLabel) {
        const container = document.getElementById('chatMessages');
        const row = document.createElement('div');
        row.className = `chat-bubble-row is-${role}`;

        const meta = document.createElement('div');
        meta.className = 'chat-sender-meta';
        if (role === 'user') {
            meta.innerHTML = `<span style="font-weight: 600; color: var(--color-text-primary);">Anda</span><span>&middot;</span><span>${metaLabel || 'Baru saja'}</span>`;
        } else {
            meta.innerHTML = `<span style="font-weight: 600; color: var(--color-teal-dark);">Chatbot SOSPEM</span><span>&middot;</span><span>${metaLabel || 'Respons'}</span>`;
        }

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble-box';
        bubble.textContent = content;

        row.appendChild(meta);
        row.appendChild(bubble);
        container.appendChild(row);
        container.scrollTop = container.scrollHeight;
        return row;
    }

    async function submitQuestion(text) {
        const sendBtn = document.getElementById('chatSendBtn');

        appendChatMessage('user', text, 'Baru saja');
        const loadingRow = appendChatMessage('assistant', 'Sedang memproses pertanyaan...', 'Memproses');
        sendBtn.disabled = true;

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
                    history: chatHistory.slice(-6)
                })
            });

            const data = await res.json();
            loadingRow.remove();

            const sourceTag = data.source === 'local_agent'
                ? `Agent Lokal (${data.agent_model || 'Qwen'}) · ${data.latency_ms || 0} ms`
                : `Database SOSPEM · ${data.latency_ms || 0} ms`;

            appendChatMessage('assistant', data.reply || 'Tidak ada respons.', sourceTag);

            if (data.status === 'ok' && data.reply) {
                chatHistory.push({ role: 'user', content: text });
                chatHistory.push({ role: 'assistant', content: data.reply });
            }
        } catch (err) {
            loadingRow.remove();
            appendChatMessage('assistant', 'Terjadi kesalahan saat menghubungi server.', 'Error');
        } finally {
            sendBtn.disabled = false;
            document.getElementById('chatInput').focus();
        }
    }
</script>
@endsection
