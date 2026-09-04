<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/csrf.php';
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
$messageRepo = new MessagePrivate($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];
$id_destinataire = filter_input(INPUT_POST, 'id_destinataire', FILTER_VALIDATE_INT);
$contenu_message = trim($_POST['contenu_message'] ?? '');

if (!$id_destinataire || $contenu_message === '' || mb_strlen($contenu_message) > 8000) {
    repondre_erreur(422, 'Message invalide.');
}

if (!$messageRepo->utilisateurExiste($id_destinataire)) {
    repondre_erreur(404, 'Destinataire introuvable.');
}

try {
    $id_message = $messageRepo->envoyerMessage($id_utilisateur, $id_destinataire, $contenu_message);
} catch (PDOException $e) {
    error_log('Erreur envoi message : ' . $e->getMessage());
    repondre_erreur(500, 'Une erreur est survenue, merci de réessayer.');
}

echo json_encode([
    'id_message_private' => $id_message,
    'account_user_emetteur' => $id_utilisateur,
    'account_user_destinataire' => $id_destinataire,
    'contenu_message' => $contenu_message,
    'date_envoi_message' => date('Y-m-d H:i:s'),
]);
exit;