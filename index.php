<?php

// session_start() doit être appelé avant tout affichage HTML — c'est ce qui
// permet à $_SESSION d'exister (nécessaire pour le jeton CSRF, et plus tard
// pour savoir si une utilisatrice est connectée)
session_start();

require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/config/csrf.php';

$page = $_GET['url'] ?? 'accueil';

// ============================================================
// Routes de traitement : ne produisent jamais de HTML, seulement
// une redirection ou un message d'erreur brut. Elles doivent être
// interceptées AVANT d'inclure header.php, car header('Location: ...')
// échoue si du HTML a déjà été envoyé au navigateur.
// ============================================================
if ($page === 'inscription/traiter') {
    include __DIR__ . '/controller/traiter_inscription.php';
    exit;
}

if ($page === 'connexion/traiter') {
    include __DIR__ . '/controller/traiter_connexion.php';
    exit;
}

include __DIR__ . '/views/includes/header.php';

switch ($page) {
    case 'accueil':
        include __DIR__ . '/views/pages/welcome.php';
        break;

    case 'inscription':
        include __DIR__ . '/views/pages/page_inscription.php';
        break;

    case 'connexion':
        include __DIR__ . '/views/pages/page_connexion.php';
        break;

    default:
        http_response_code(404);
        echo '<p>Page introuvable.</p>';
        break;
}

include __DIR__ . '/views/includes/footer.php';