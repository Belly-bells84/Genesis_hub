<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../models/class_publication.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide, merci de recharger la page et de réessayer.');
}

$pdo = obtenir_connexion();
$publicationRepo = new Publication($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];
$contenu_publication = trim($_POST['contenu_publication'] ?? '');

if ($contenu_publication === '' || mb_strlen($contenu_publication) > 1500) {
    http_response_code(422);
    exit('Contenu de publication invalide.');
}

try {
    $publicationRepo->creer($id_utilisateur, $contenu_publication);
} catch (PDOException $e) {
    error_log('Erreur création publication : ' . $e->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue, merci de réessayer.');
}

header('Location: /feed');
exit;