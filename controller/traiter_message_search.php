<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../models/class_message.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

function repondre_erreur(int $code, string $message): never
{
    http_response_code($code);
    echo json_encode(['erreur' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    repondre_erreur(405, 'Méthode non autorisée.');
}

if (!isset($_SESSION['user_id'])) {
    repondre_erreur(401, 'Vous devez être connecté.');
}

$pdo = obtenir_connexion();
$messageRepo = new MessagePrivate($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];
$terme = trim($_GET['q'] ?? '');

// Sous 2 caractères, on ne cherche pas : trop de résultats non pertinents,
// et ça évite de spammer la base de données à chaque frappe.
if (mb_strlen($terme) < 2) {
    echo json_encode([]);
    exit;
}

$resultats = $messageRepo->rechercherUtilisateurs($terme, $id_utilisateur);

echo json_encode($resultats);
exit;