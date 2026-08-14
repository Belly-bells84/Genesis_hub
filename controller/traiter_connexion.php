<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide, merci de recharger la page et de réessayer.');
}

$pdo = obtenir_connexion();

$email_user = trim($_POST['email_user'] ?? '');
$password_user = $_POST['password_user'] ?? '';

const MAX_TENTATIVES = 5;
const DUREE_BLOCAGE_MINUTES = 15;

$stmt = $pdo->prepare('
    SELECT id, password_user, tentatives_echouees, bloque_jusqu_a
    FROM account_user
    WHERE email_user = ?
');
$stmt->execute([$email_user]);
$compte = $stmt->fetch();

// Compte inexistant : on renvoie exactement le même message d'erreur générique que pour un mauvais mot de passe, pour ne jamais révéler si un email est inscrit ou non ("énumération de comptes" — sinon un attaquant pourrait tester des emails en masse pour savoir lesquels existent sur GENESIS)
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
        $stmt_blocage = $pdo->prepare('
            UPDATE account_user
            SET tentatives_echouees = 0,
                bloque_jusqu_a = DATE_ADD(NOW(), INTERVAL ' . DUREE_BLOCAGE_MINUTES . ' MINUTE)
            WHERE id = ?
        ');
        $stmt_blocage->execute([$compte['id']]);
        header('Location: /connexion?bloque=1');
        exit;
    }

    $stmt_echec = $pdo->prepare('UPDATE account_user SET tentatives_echouees = ? WHERE id = ?');
    $stmt_echec->execute([$nouvelles_tentatives, $compte['id']]);

    header('Location: /connexion?erreur=1');
    exit;
}

// Condition : connexion réussie : réinitialiser le compteur d'échecs
$stmt_reset = $pdo->prepare('UPDATE account_user SET tentatives_echouees = 0, bloque_jusqu_a = NULL WHERE id = ?');
$stmt_reset->execute([$compte['id']]);

// Régénérer l'identifiant de session après une connexion réussie :
// empêche une attaque de "fixation de session" (un attaquant qui aurait
// connu l'ID de session avant la connexion ne peut pas l'exploiter après)
session_regenerate_id(true);

$_SESSION['user_id'] = $compte['id'];

header('Location: /');
exit;