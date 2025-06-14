// Rafraîchissement automatique des messages (à compléter selon ta logique d'API)
document.addEventListener('DOMContentLoaded', function() {
    const messagesDiv = document.getElementById('messages');
    const sendForm = document.getElementById('sendForm');
    if (messagesDiv && sendForm) {
        function refreshMessages() {
            const to = sendForm.elements['to'].value;
            fetch('messages.php?user=' + encodeURIComponent(to))
                .then(response => response.text())
                .then(data => { messagesDiv.innerHTML = data; });
        }
        refreshMessages();
        setInterval(refreshMessages, 2000);

        sendForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(sendForm);
            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(() => {
                sendForm.elements['message'].value = '';
                refreshMessages();
            });
        });
    }
});