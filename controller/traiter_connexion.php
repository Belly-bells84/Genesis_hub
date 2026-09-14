<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/theme.php';
require_once __DIR__ . '/../models/class_user.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide, merci de recharger la page et de réessayer.');
}

$pdo = obtenir_connexion();
$userRepo = new User($pdo);

$email_user = trim($_POST['email_user'] ?? '');
$password_user = $_POST['password_user'] ?? '';

const MAX_TENTATIVES = 5;
const DUREE_BLOCAGE_MINUTES = 15;

$compte = $userRepo->recupParEmail($email_user);

// Compte inexistant : on renvoie exactement le même message d'erreur générique
// que pour un mauvais mot de passe, pour ne jamais révéler si un email est
// inscrit ou non ("énumération de comptes").
if (!$compte) {
    header('Location: /connexion?erreur=1');
    exit;
}

// Vérification du blocage temporaire (anti brute-force)
if ($compte['bloque_jusqu_a'] !== null && new DateTime($compte['bloque_jusqu_a']) > new DateTime()) {
    header('Location: /connexion?bloque=1');
    exit;
}

// Vérification du mot de passe
if (!password_verify($password_user, $compte['password_user'])) {
    $nouvelles_tentatives = $compte['tentatives_echouees'] + 1;

    if ($nouvelles_tentatives >= MAX_TENTATIVES) {
        $userRepo->bloquer($compte['id'], DUREE_BLOCAGE_MINUTES);
        header('Location: /connexion?bloque=1');
        exit;
    }

    $userRepo->enregistrerEchecConnexion($compte['id'], $nouvelles_tentatives);
    header('Location: /connexion?erreur=1');
    exit;
}

// Connexion réussie : réinitialiser le compteur d'échecs
$userRepo->reinitialiserTentatives($compte['id']);

// Régénérer l'identifiant de session après une connexion réussie :
// empêche une attaque de "fixation de session"
session_regenerate_id(true);

$_SESSION['user_id'] = $compte['id'];
$_SESSION['color'] = theme_vers_classe_css($compte['theme']);

header('Location: /');
exit;