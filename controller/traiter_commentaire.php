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
$id_publication = filter_input(INPUT_POST, 'id_publication', FILTER_VALIDATE_INT);
$contenu_commentaire = trim($_POST['contenu_commentaire'] ?? '');

if (!$id_publication || $contenu_commentaire === '' || mb_strlen($contenu_commentaire) > 1500) {
    http_response_code(422);
    exit('Commentaire invalide.');
}

if (!$publicationRepo->existe($id_publication)) {
    http_response_code(404);
    exit('Publication introuvable.');
}

try {
    $publicationRepo->creerCommentaire($id_utilisateur, $id_publication, $contenu_commentaire);
} catch (PDOException $e) {
    error_log('Erreur création commentaire : ' . $e->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue, merci de réessayer.');
}

header('Location: /feed');
exit;