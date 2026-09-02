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

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Vous devez être connecté.');
}

$pdo = obtenir_connexion();
$publicationRepo = new Publication($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];
$id_publication = (int) ($_POST['id_publication'] ?? 0);

if (!$publicationRepo->existe($id_publication)) {
    http_response_code(404);
    exit('Publication introuvable.');
}

// Empêche une utilisatrice de supprimer la publication d'une autre,
// même en connaissant ou en devinant son id_publication.
if (!$publicationRepo->estAuteure($id_utilisateur, $id_publication)) {
    http_response_code(403);
    exit('Vous n\'êtes pas autorisée à supprimer cette publication.');
}

$chemin_media = $publicationRepo->recupCheminMedia($id_publication);

$publicationRepo->supprimer($id_publication);

if ($chemin_media !== null) {
    @unlink(__DIR__ . '/..' . $chemin_media);
}

header('Location: /feed');
exit;