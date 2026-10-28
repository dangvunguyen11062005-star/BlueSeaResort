/* Chatbot AI tư vấn khách hàng - nút nổi góc phải dưới. Không dùng innerHTML với nội dung từ máy chủ (chống XSS). */
(function () {
    const root = document.getElementById('ai-chat');
    if (!root) return;
    const endpoint = root.dataset.endpoint, csrf = root.dataset.csrf;
    const toggle = root.querySelector('.ai-chat-toggle'), panel = root.querySelector('.ai-chat-panel');
    const log = root.querySelector('.ai-chat-log'), form = root.querySelector('form'), input = form.querySelector('input');
    const sendBtn = form.querySelector('button'), chips = root.querySelector('.ai-chat-chips');
    const KEY = 'bluesea_chat_v1';
    let history = [];
    let busy = false;

    const load = () => { try { history = JSON.parse(sessionStorage.getItem(KEY)) || []; } catch (e) { history = []; } };
    const save = () => { try { sessionStorage.setItem(KEY, JSON.stringify(history.slice(-20))); } catch (e) { /* bỏ qua */ } };

    /* Hiển thị an toàn: **đậm**, xuống dòng, và biến http(s) URL thành liên kết */
    function render(el, text) {
        el.textContent = '';
        text.split('\n').forEach((line, i) => {
            if (i) el.appendChild(document.createElement('br'));
            line.split(/(\*\*[^*]+\*\*|https?:\/\/[^\s)]+)/g).forEach(part => {
                if (!part) return;
                if (/^\*\*[^*]+\*\*$/.test(part)) { const b = document.createElement('b'); b.textContent = part.slice(2, -2); el.appendChild(b); }
                else if (/^https?:\/\//.test(part)) {
                    const a = document.createElement('a'); a.href = part; a.rel = 'noopener';
                    a.textContent = /booking\.php/.test(part) ? 'Đặt phòng →' : /room-detail\.php/.test(part) ? 'Xem chi tiết →' : part;
                    if (new URL(part, location.href).origin !== location.origin) a.target = '_blank';
                    el.appendChild(a);
                } else el.appendChild(document.createTextNode(part));
            });
        });
    }
    function add(role, text) {
        const m = document.createElement('div');
        m.className = 'ai-msg ai-msg-' + (role === 'user' ? 'user' : 'bot');
        render(m, text);
        log.appendChild(m);
        log.scrollTop = log.scrollHeight;
        return m;
    }

    async function send(text) {
        text = text.trim();
        if (!text || busy) return;
        busy = true; sendBtn.disabled = true;
        if (chips) chips.hidden = true;
        add('user', text);
        history.push({ role: 'user', content: text });
        const wait = add('bot', 'Đang soạn câu trả lời…'); wait.classList.add('ai-typing');
        try {
            const res = await fetch(endpoint, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ messages: history.slice(-12) })
            });
            const j = await res.json().catch(() => ({}));
            wait.classList.remove('ai-typing');
            if (!res.ok) { render(wait, (j.error && j.error.message) || 'Có lỗi xảy ra, vui lòng thử lại.'); history.pop(); }
            else { render(wait, j.data.reply); history.push({ role: 'assistant', content: j.data.reply }); }
        } catch (e) {
            wait.classList.remove('ai-typing'); render(wait, 'Không kết nối được máy chủ. Vui lòng thử lại.'); history.pop();
        }
        save(); busy = false; sendBtn.disabled = false; input.focus(); log.scrollTop = log.scrollHeight;
    }

    function open(state) {
        panel.hidden = !state;
        toggle.setAttribute('aria-expanded', state ? 'true' : 'false');
        root.classList.toggle('open', state);
        if (state) { input.focus(); log.scrollTop = log.scrollHeight; }
    }

    load();
    add('bot', root.dataset.welcome);
    history.forEach(m => add(m.role, m.content));
    if (history.length && chips) chips.hidden = true;

    toggle.addEventListener('click', () => open(panel.hidden));
    root.querySelector('.ai-chat-close').addEventListener('click', () => { open(false); toggle.focus(); });
    root.addEventListener('keydown', e => { if (e.key === 'Escape' && !panel.hidden) { open(false); toggle.focus(); } });
    form.addEventListener('submit', e => { e.preventDefault(); const t = input.value; input.value = ''; send(t); });
    if (chips) chips.addEventListener('click', e => { const b = e.target.closest('button[data-q]'); if (b) send(b.dataset.q); });
})();
