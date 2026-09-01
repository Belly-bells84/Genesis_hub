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

if (!$id_publication) {
    http_response_code(422);
    exit('Publication invalide.');
}

if (!$publicationRepo->existe($id_publication)) {
    http_response_code(404);
    exit('Publication introuvable.');
}

$publicationRepo->basculerLike($id_utilisateur, $id_publication);

header('Location: /feed');
exit;