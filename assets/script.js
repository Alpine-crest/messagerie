document.addEventListener('DOMContentLoaded', function() {
    const messagesDiv = document.getElementById('chat-messages');
    const msgInput = document.getElementById('message-input');
    const form = document.getElementById('chat-form');
    const toInput = document.getElementById('chat-to');

    // Récup info du contact
    const contact = window.APP_CHAT ? window.APP_CHAT.contact : '';
    const myUsername = window.APP_CHAT ? window.APP_CHAT.myUsername : '';

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    function loadMessages() {
        if (!contact || !messagesDiv) return;
        fetch('messages_api.php?contact=' + encodeURIComponent(contact))
            .then(r => r.json())
            .then(data => {
                messagesDiv.innerHTML = "";
                if (data.messages) {
                    data.messages.forEach(msg => {
                        const el = document.createElement('div');
                        el.className = 'msg' + (msg.sender_username === myUsername ? ' self' : '');
                        el.innerHTML = `<b>${escapeHtml(msg.sender_username)}:</b> ${escapeHtml(msg.content)} <span class="date">${escapeHtml(msg.sent_at)}</span>`;
                        messagesDiv.appendChild(el);
                    });
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                }
            });
    }

    // Rafraîchissement périodique
    setInterval(loadMessages, 2000);
    loadMessages();

    // Envoi AJAX du message
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            fetch('send_message.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                msgInput.value = '';
                loadMessages();
            });
        });
    }
});