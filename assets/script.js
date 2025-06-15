document.addEventListener('DOMContentLoaded', function() {
    const messagesDiv = document.getElementById('chat-messages');
    const msgInput = document.getElementById('message-input');
    const form = document.getElementById('chat-form');
    const errorDiv = document.getElementById('chat-error');
    const toInput = document.getElementById('chat-to');
    const csrfInput = document.getElementById('csrf-token');

    function getCsrfToken() {
        // Toujours lire la valeur du champ caché ! (c'est la source de vérité)
        return csrfInput ? csrfInput.value : '';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    function showError(msg) {
        if (errorDiv) {
            errorDiv.textContent = msg;
            errorDiv.style.display = '';
            setTimeout(() => { errorDiv.style.display = 'none'; }, 4000);
        } else {
            alert(msg);
        }
    }

    function loadMessages() {
        const contact = window.APP_CHAT ? window.APP_CHAT.contact : '';
        const myUsername = window.APP_CHAT ? window.APP_CHAT.myUsername : '';
        if (!contact || !messagesDiv) {
            messagesDiv.innerHTML = "<i>Sélectionnez un contact pour discuter.</i>";
            return;
        }
        fetch('messages_api.php?contact=' + encodeURIComponent(contact), {
            credentials: "same-origin"
        })
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
                } else if (data.error) {
                    showError(data.error);
                }
            })
            .catch(() => { showError("Erreur de connexion ou d'accès aux messages."); });
    }

    // Rafraîchissement périodique
    setInterval(loadMessages, 2000);
    loadMessages();

    // Envoi AJAX du message avec CSRF dynamique
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            errorDiv && (errorDiv.style.display = "none");
            const formData = new FormData(form);
            formData.set('csrf_token', getCsrfToken());
            fetch('send_message.php', {
                method: 'POST',
                credentials: "same-origin",
                body: formData
            }).then(async response => {
                if (response.ok) {
                    let data = {};
                    try { data = await response.json(); } catch (e) {}
                    // Nouveau token CSRF pour le prochain envoi
                    if (data.csrf_token) {
                        csrfInput.value = data.csrf_token;
                        window.APP_CHAT.csrfToken = data.csrf_token;
                    }
                    msgInput.value = '';
                    loadMessages();
                } else {
                    let txt = await response.text();
                    showError(txt || "Erreur lors de l'envoi.");
                }
            }).catch(() => {
                showError("Erreur réseau.");
            });
        });
    }
});