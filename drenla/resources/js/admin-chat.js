function initAdminChatPolling() {
    const poll = document.querySelector('[data-chat-poll]');
    const log = document.querySelector('[data-transcript-log]');
    if (!poll || !log) {
        return;
    }

    const url = poll.dataset.messagesUrl;
    const statusBadge = document.querySelector('[data-ai-status-badge]');
    const statusLabel = document.querySelector('[data-ai-status-label]');
    let afterId = parseInt(log.dataset.afterId || '0', 10);
    let timer = null;

    const bubbleClasses = {
        user: 'border-[#1f1f1f] bg-black text-[#ccc]',
        assistant: 'border-[#2a2a3a] bg-[#0d0c14] text-white',
        admin: 'border-white/25 bg-white text-black',
    };

    function appendMessage(message) {
        const empty = log.querySelector('[data-transcript-empty]');
        if (empty) {
            empty.remove();
        }

        const isVisitor = message.role === 'user';
        const isAdmin = message.role === 'admin';
        const label = isVisitor ? 'Visitor' : (isAdmin ? (message.sender_name || 'Admin') : 'Assistant');

        const row = document.createElement('div');
        row.className = `flex flex-col ${isVisitor ? 'items-start' : 'items-end'}`;
        row.dataset.messageRow = '';
        row.dataset.messageId = message.id;

        const meta = document.createElement('p');
        meta.className = 'mb-1 text-[9px] font-black uppercase tracking-[0.2em] text-[#444]';
        meta.textContent = `${label} · ${message.created_at}`;
        row.appendChild(meta);

        const bubble = document.createElement('div');
        bubble.className = `max-w-[85%] border px-4 py-3 text-[13px] leading-relaxed whitespace-pre-wrap ${bubbleClasses[message.role] || bubbleClasses.assistant}`;
        bubble.textContent = message.content;
        row.appendChild(bubble);

        log.appendChild(row);
        log.scrollTop = log.scrollHeight;
    }

    function updateStatus(aiEnabled) {
        if (!statusBadge || !statusLabel) {
            return;
        }

        statusLabel.textContent = aiEnabled ? 'AI replying' : 'Human handling';
        statusBadge.classList.toggle('border-[#77ccff]/30', aiEnabled);
        statusBadge.classList.toggle('text-[#77ccff]', aiEnabled);
        statusBadge.classList.toggle('border-[#ccaa77]/30', !aiEnabled);
        statusBadge.classList.toggle('text-[#ccaa77]', !aiEnabled);
    }

    async function tick() {
        try {
            const response = await fetch(`${url}?after_id=${afterId}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            (data.messages || []).forEach((message) => {
                appendMessage(message);
                afterId = Math.max(afterId, message.id);
            });

            updateStatus(data.ai_enabled);
        } catch {
            // Silently retry on the next interval — a dropped poll isn't worth surfacing.
        }
    }

    function schedule() {
        const interval = document.hidden ? 20000 : 5000;
        timer = window.setTimeout(async () => {
            await tick();
            schedule();
        }, interval);
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            window.clearTimeout(timer);
            tick().then(schedule);
        }
    });

    schedule();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminChatPolling);
} else {
    initAdminChatPolling();
}
