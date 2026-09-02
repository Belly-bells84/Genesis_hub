<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../models/class_message.php';
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

// Route de lecture seule (pas de header('Location: ...'), pas de
// modification autre que marquer les messages comme lus) : pas besoin
// de jeton CSRF ici, seulement de vérifier la session.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    repondre_erreur(405, 'Méthode non autorisée.');
}

if (!isset($_SESSION['user_id'])) {
    repondre_erreur(401, 'Vous devez être connecté.');
}

$pdo = obtenir_connexion();
$messageRepo = new MessagePrivate($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];
$id_contact = filter_input(INPUT_GET, 'id_contact', FILTER_VALIDATE_INT);
$depuis_id = filter_input(INPUT_GET, 'depuis_id', FILTER_VALIDATE_INT);
$depuis_id = $depuis_id !== false && $depuis_id !== null ? $depuis_id : null;

if (!$id_contact) {
    repondre_erreur(422, 'Contact invalide.');
}

if (!$messageRepo->utilisateurExiste($id_contact)) {
    repondre_erreur(404, 'Contact introuvable.');
}

$messages = $messageRepo->recupMessages($id_utilisateur, $id_contact, $depuis_id);

// On marque comme lus les messages reçus de ce contact à chaque appel :
// comme la personne a la conversation ouverte (elle poll activement),
// on considère qu'elle voit les nouveaux messages au fur et à mesure.
$messageRepo->marquerCommeLu($id_utilisateur, $id_contact);

echo json_encode($messages);
exit;