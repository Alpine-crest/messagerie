<?php
// Ajoute ici des fonctions utiles (sécurité, gestion contacts, etc.)

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>