<?php

// session_start() doit être appelé avant tout affichage HTML — c'est ce qui
// permet à $_SESSION d'exister (nécessaire pour le jeton CSRF, et plus tard
// pour savoir si une utilisatrice est connectée)
session_start();

require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/auth.php';

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

if ($page === 'profil/traiter') {
    include __DIR__ . '/controller/traiter_profil.php';
    exit;
}

if ($page === 'profil/mot-de-passe/traiter') {
    include __DIR__ . '/controller/traiter_mot_de_passe.php';
    exit;
}

if ($page === 'deconnexion') {
    $_SESSION = [];
    session_destroy();
    header('Location: /connexion');
    exit;
}

// ============================================================
// Contrôle d'accès en liste blanche : toute page absente de cette
// liste exige une connexion. Une nouvelle page ajoutée plus tard
// est donc protégée par défaut, sans avoir à y penser à chaque fois.
// ============================================================
$pages_publiques = ['accueil', 'inscription', 'connexion'];

if (!in_array($page, $pages_publiques, true)) {
    exiger_connexion();
}

// ============================================================
// /profil          => son propre profil (édition)
// /profil/{id}     => le profil d'une autre utilisatrice (lecture seule)
// Les deux routes sont normalisées vers le même $page='profil' pour
// que le switch les traite ensemble ; $id_profil_consulte distingue
// les deux cas dans page_profil.php.
// ============================================================
$id_profil_consulte = null;

if ($page === 'profil') {
    $id_profil_consulte = (int) $_SESSION['user_id'];
} elseif (preg_match('#^profil/(\d+)$#', $page, $matches)) {
    $id_profil_consulte = (int) $matches[1];
    $page = 'profil';
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

    case 'profil':
        include __DIR__ . '/views/pages/page_profil.php';
        break;

    default:
        http_response_code(404);
        echo '<p>Page introuvable.</p>';
        break;
}

include __DIR__ . '/views/includes/footer.php';