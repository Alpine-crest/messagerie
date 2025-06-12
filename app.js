// Variables globales
let currentUser = null;
let currentPrivateKey = null; // OpenPGP Key
let conversations = [];
let currentConvo = null;
let pollInterval = null;

// --- Notifications
function notify(msg, type='info') {
    const notif = document.getElementById('notif');
    notif.innerText = msg;
    notif.style.background = type==='error' ? '#eb5757' : '#2f80ed';
    notif.className = 'show';
    setTimeout(()=>notif.className='', 2200);
}

// --- Échappement strict côté client (aucun HTML dans les messages/pseudos)
function safeHtml(text) {
    return text.replace(/[&<>"']/g, function (c) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[c];
    });
}

// --- Bloquer toute action si non connecté
function requireConnected() {
    if (!currentUser) {
        notify('Vous devez être connecté','error');
        throw new Error('Not connected');
    }
}

// --- Auth
async function login() {
    if (currentUser) {
        notify('Déjà connecté. Déconnectez-vous d\'abord.', 'error');
        return;
    }
    const login = document.getElementById('login').value.trim();
    const pass = document.getElementById('password').value;
    const totp = document.getElementById('totp') ? document.getElementById('totp').value : '';
    if (!login || !pass) return notify('Remplir tous les champs','error');
    let payload = {login, password: pass};
    if (totp) payload.totp = totp;
    const res = await fetch('login.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.status==='success') {
        currentUser = data.username;
        sessionStorage.setItem('username', currentUser);
        notify('Connecté');
        document.getElementById('auth').style.display='none';
        document.getElementById('btn_logout').style.display='';
        document.getElementById('msgbox').style.display='';
        document.getElementById('profile').style.display = '';
        loadConversations();
        unlockPrivateKey();
    } else if (data.captcha_question) {
        let answer = prompt(data.captcha_question);
        // Retry login with captcha
        payload.captcha = answer;
        const res2 = await fetch('login.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify(payload)
        });
        const data2 = await res2.json();
        if (data2.status==='success') {
            currentUser = data2.username;
            sessionStorage.setItem('username', currentUser);
            notify('Connecté');
            document.getElementById('auth').style.display='none';
            document.getElementById('btn_logout').style.display='';
            document.getElementById('msgbox').style.display='';
            document.getElementById('profile').style.display = '';
            loadConversations();
            unlockPrivateKey();
        } else {
            notify(data2.message || "Erreur captcha", "error");
        }
    } else {
        notify(data.message,'error');
    }
}
document.getElementById('btn_login').onclick = login;
document.getElementById('btn_logout').onclick = function() { location.reload(); }
document.getElementById('btn_to_register').onclick = function() {
    if (currentUser) return notify('Déconnectez-vous d\'abord.','error');
    document.getElementById('auth').style.display='none';
    document.getElementById('register').style.display='';
}
document.getElementById('btn_to_login').onclick = function() {
    document.getElementById('register').style.display='none';
    document.getElementById('auth').style.display='';
}

// --- Inscription
document.getElementById('btn_register').onclick = async function() {
    let username = document.getElementById('reg_username').value.trim();
    let email = document.getElementById('reg_email').value.trim();
    let password = document.getElementById('reg_password').value;
    let passphrase = document.getElementById('reg_passphrase').value;
    if (!username || !email || !password || !passphrase) return notify('Champs requis','error');
    // Générer clé OpenPGP
    const { privateKey, publicKey } = await openpgp.generateKey({
        type: 'rsa',
        rsaBits: 2048,
        userIDs: [{ name: username, email }],
        passphrase
    });
    // Inscription serveur
    const res = await fetch('register.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({
        username, email, password, public_key: publicKey
    })});
    const data = await res.json();
    if (data.status==='success') {
        notify('Inscription réussie');
        localStorage.setItem('privKey', privateKey); // Stocke la clé privée chiffrée
        document.getElementById('register').style.display='none';
        document.getElementById('auth').style.display='';
        // Affiche QRcode 2FA si présent
        if (data.twofa_qr_url) {
            let qrdiv = document.getElementById('qr2fa');
            qrdiv.innerHTML = '';
            let img = document.createElement('img');
            img.src = data.twofa_qr_url;
            qrdiv.appendChild(img);
            qrdiv.style.display = '';
            notify('Scannez le QR code dans Google Authenticator.');
        }
    } else notify(data.message,'error');
}

// --- Déverrouille la clé privée
async function unlockPrivateKey() {
    let privKeyArmored = localStorage.getItem('privKey');
    if (!privKeyArmored) return notify('Veuillez importer/générer votre clé privée','error');
    let passphrase = prompt('Entrez votre passphrase OpenPGP (clé privée)');
    const { key } = await openpgp.readPrivateKey({ armoredKey: privKeyArmored });
    currentPrivateKey = await openpgp.decryptKey({ privateKey: key, passphrase });
    if (!currentPrivateKey) notify('Passphrase incorrecte','error');
}

// --- UI/UX conversations
async function loadConversations() {
    requireConnected();
    const res = await fetch('get_conversations.php');
    const data = await res.json();
    const ul = document.getElementById('conversations');
    ul.innerHTML = '';
    conversations = data.conversations;
    for (let c of (conversations||[])) {
        let li = document.createElement('li');
        li.innerText = safeHtml(c.username);
        li.onclick = ()=>selectConversation(c.username);
        if (c.username === currentConvo) li.classList.add('active');
        ul.appendChild(li);
    }
    if (!currentConvo && conversations.length) selectConversation(conversations[0].username);
}

// --- Sélection conversation
async function selectConversation(username) {
    requireConnected();
    currentConvo = username;
    document.getElementById('current_convo').innerText = safeHtml(username);
    document.querySelectorAll('#conversations li').forEach(li=>li.classList.toggle('active',li.innerText===username));
    loadMessages();
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(loadMessages, 3000);
}

// --- Envoi message
document.getElementById('btn_send').onclick = async function() {
    requireConnected();
    const msg = document.getElementById('message').value;
    if (!msg) return;
    // Récupère la clé publique du destinataire
    const r = await fetch('get_public_key.php?username='+encodeURIComponent(currentConvo));
    const d = await r.json();
    if (!d.public_key) return notify("Destinataire sans clé publique",'error');
    const pubKey = await openpgp.readKey({ armoredKey: d.public_key });
    // Chiffre le message avec la clé publique
    const encrypted = await openpgp.encrypt({
        message: await openpgp.createMessage({ text: msg }),
        encryptionKeys: pubKey,
        signingKeys: currentPrivateKey
    });
    await fetch('send_message.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ receiver: currentConvo, encrypted_message: encrypted })
    });
    document.getElementById('message').value = '';
    loadMessages();
}

// --- Charge les messages d'une conversation
async function loadMessages() {
    requireConnected();
    const res = await fetch('get_messages.php?with='+encodeURIComponent(currentConvo));
    const data = await res.json();
    const cont = document.getElementById('messages');
    cont.innerHTML = '';
    for (let msg of (data.messages||[])) {
        let txt = '';
        try {
            const message = await openpgp.readMessage({ armoredMessage: msg.encrypted_message });
            const { data: decrypted } = await openpgp.decrypt({
                message,
                decryptionKeys: currentPrivateKey
            });
            txt = safeHtml(decrypted);
        } catch(e) { txt = '[Indéchiffrable]'; }
        let div = document.createElement('div');
        div.className = 'msg ' + (msg.sender_id == currentUser ? 'me':'other');
        div.innerText = txt;
        cont.appendChild(div);
    }
    cont.scrollTop = cont.scrollHeight;
}

// --- Export de clé privée (PGP) sécurisé
window.exportPrivateKey = function() {
    requireConnected();
    const priv = localStorage.getItem('privKey');
    if (!priv) return notify('Aucune clé privée à exporter','error');
    const blob = new Blob([priv], {type:'text/plain'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'privatekey.asc';
    a.click();
    notify('Clé privée exportée');
};

// --- Import de clé privée (PGP)
window.importPrivateKey = function(input) {
    requireConnected();
    const file = input.files[0];
    if (!file) return;
    file.text().then(txt => {
        if (!txt.startsWith('-----BEGIN PGP PRIVATE KEY')) {
            notify('Format de clé incorrect','error');
            return;
        }
        localStorage.setItem('privKey', txt);
        notify('Clé privée importée');
    });
};

// Désactive tous les éléments sensibles si pas connecté
window.onload = function() {
    if (!currentUser) {
        document.getElementById('msgbox').style.display = 'none';
        document.getElementById('profile').style.display = 'none';
    }
};