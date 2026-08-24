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
$id_utilisateur = (int) $_SESSION['user_id'];

$mot_de_passe_actuel = $_POST['mot_de_passe_actuel'] ?? '';
$nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'] ?? '';
$nouveau_mot_de_passe_confirmation = $_POST['nouveau_mot_de_passe_confirmation'] ?? '';

$stmt = $pdo->prepare('SELECT password_user FROM account_user WHERE id = ?');
$stmt->execute([$id_utilisateur]);
$compte = $stmt->fetch();

$mot_de_passe_valide = $compte && password_verify($mot_de_passe_actuel, $compte['password_user']);
$nouveaux_mots_de_passe_valides = mb_strlen($nouveau_mot_de_passe) >= 8
    && $nouveau_mot_de_passe === $nouveau_mot_de_passe_confirmation;

if (!$mot_de_passe_valide || !$nouveaux_mots_de_passe_valides) {
    header('Location: /profil?erreur_mdp=1');
    exit;
}

$nouveau_hash = password_hash($nouveau_mot_de_passe, PASSWORD_BCRYPT);

$stmt_update = $pdo->prepare('UPDATE account_user SET password_user = ? WHERE id = ?');
$stmt_update->execute([$nouveau_hash, $id_utilisateur]);

header('Location: /profil?succes=1');
exit;