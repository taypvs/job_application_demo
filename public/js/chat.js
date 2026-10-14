(function () {
    const toggle = document.getElementById('chat-toggle');
    const close = document.getElementById('chat-close');
    const panel = document.getElementById('chat-panel');
    const log = document.getElementById('chat-log');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let loaded = false;

    toggle.addEventListener('click', function () {
        setOpen(panel.hasAttribute('hidden'));
    });
    close.addEventListener('click', function () {
        setOpen(false);
    });

    async function setOpen(open) {
        if (open) {
            panel.removeAttribute('hidden');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.textContent = '閉じる';
            if (!loaded) {
                await loadHistory();
                loaded = true;
            }
            input.focus();
            return;
        }
        panel.setAttribute('hidden', '');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.textContent = '相談';
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) {
            return;
        }
        add('user', message);
        input.value = '';
        const pending = add('assistant', '考えています…');
        form.querySelector('button').disabled = true;

        try {
            const response = await fetch('/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ message: message }),
            });
            pending.remove();
            if (response.status === 419) {
                add('assistant', 'セッションの有効期限が切れました。ページを再読み込みしてください。');
                return;
            }
            const data = await response.json();
            if (!response.ok) {
                const error = data.errors ? Object.values(data.errors).flat()[0] : '送信に失敗しました。';
                add('assistant', error);
                return;
            }
            add('assistant', data.reply || '');
        } catch (error) {
            pending.remove();
            add('assistant', '通信に失敗しました。');
        } finally {
            form.querySelector('button').disabled = false;
        }
    });

    async function loadHistory() {
        const response = await fetch('/chat', { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
            return;
        }
        const data = await response.json();
        (data.messages || []).forEach(function (message) {
            add(message.role, message.content);
        });
    }

    function add(role, content) {
        const item = document.createElement('div');
        item.className = 'bubble ' + (role === 'user' ? 'user' : 'assistant');
        item.textContent = content;
        log.appendChild(item);
        log.scrollTop = log.scrollHeight;
        return item;
    }
})();
