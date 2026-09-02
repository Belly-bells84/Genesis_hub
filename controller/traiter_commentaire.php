<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../models/class_publication.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

/**
 * Envoie une réponse JSON d'erreur avec le bon code HTTP, et arrête le script.
 */
function repondre_erreur(int $code, string $message): never
{
    http_response_code($code);
    echo json_encode(['erreur' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    repondre_erreur(405, 'Méthode non autorisée.');
}

if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    repondre_erreur(403, 'Requête invalide, merci de recharger la page et de réessayer.');
}

if (!isset($_SESSION['user_id'])) {
    repondre_erreur(401, 'Vous devez être connecté.');
}

$pdo = obtenir_connexion();
$publicationRepo = new Publication($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];
$id_publication = filter_input(INPUT_POST, 'id_publication', FILTER_VALIDATE_INT);
$contenu_commentaire = trim($_POST['contenu_commentaire'] ?? '');

if (!$id_publication || $contenu_commentaire === '' || mb_strlen($contenu_commentaire) > 1500) {
    repondre_erreur(422, 'Commentaire invalide.');
}

if (!$publicationRepo->existe($id_publication)) {
    repondre_erreur(404, 'Publication introuvable.');
}

try {
    $id_commentaire = $publicationRepo->creerCommentaire($id_utilisateur, $id_publication, $contenu_commentaire);
} catch (PDOException $e) {
    error_log('Erreur création commentaire : ' . $e->getMessage());
    repondre_erreur(500, 'Une erreur est survenue, merci de réessayer.');
}

$commentaire = $publicationRepo->recupCommentaire($id_commentaire);

echo json_encode($commentaire);
exit;