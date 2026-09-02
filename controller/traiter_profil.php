<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/chiffrement.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../models/class_user.php';
require_once __DIR__ . '/../models/valid_ref_profil.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide, merci de recharger la page et de réessayer.');
}

$pdo = obtenir_connexion();
$userRepo = new User($pdo);
$id_utilisateur = (int) $_SESSION['user_id'];
$erreurs = [];

$account_name = trim($_POST['account_name'] ?? '');
$desc_name = trim($_POST['desc_name'] ?? '');
$work_user = trim($_POST['work_user'] ?? '');
$phone_user = trim($_POST['phone_user'] ?? '');
$city_user = trim($_POST['city_user'] ?? '');
$id_corps_armee = filter_input(INPUT_POST, 'id_corps_armee', FILTER_VALIDATE_INT);
$id_sous_corps_armee = filter_input(INPUT_POST, 'id_sous_corps_armee', FILTER_VALIDATE_INT) ?: null;
$id_situation = filter_input(INPUT_POST, 'id_situation', FILTER_VALIDATE_INT);
$id_sous_situation = filter_input(INPUT_POST, 'id_sous_situation', FILTER_VALIDATE_INT) ?: null;
$reg_visible = isset($_POST['reg_visible']) ? 1 : 0;

if ($account_name === '' || mb_strlen($account_name) > 150) {
    $erreurs[] = 'Pseudo invalide.';
}

// Validation factorisée, partagée avec traiter_inscription.php
valid_ref_profil($pdo, $id_corps_armee, $id_sous_corps_armee, $id_situation, $id_sous_situation, $erreurs);

if (!empty($erreurs)) {
    http_response_code(422);
    foreach ($erreurs as $erreur) {
        echo '<p>' . htmlspecialchars($erreur) . '</p>';
    }
    exit;
}

// Photo (facultative, ne remplace l'ancienne que si une nouvelle est envoyée)
$chemin_photo = null;

if (!empty($_FILES['pictures_user']['name'])) {
    $extensions_autorisees = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($_FILES['pictures_user']['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensions_autorisees, true)) {
        http_response_code(422);
        exit('Format de photo non autorisé.');
    }

    if ($_FILES['pictures_user']['size'] > 5 * 1024 * 1024) {
        http_response_code(422);
        exit('Photo trop volumineuse (5 Mo maximum).');
    }

    $nom_fichier = bin2hex(random_bytes(16)) . '.' . $extension;
    $chemin_destination = __DIR__ . '/../asset/IMG/uploads/' . $nom_fichier;

    if (!move_uploaded_file($_FILES['pictures_user']['tmp_name'], $chemin_destination)) {
        http_response_code(500);
        exit('Échec de l\'envoi de la photo.');
    }

    $chemin_photo = '/asset/IMG/uploads/' . $nom_fichier;
}

$phone_chiffre = $phone_user !== '' ? chiffrer($phone_user) : null;
$city_chiffree = $city_user !== '' ? chiffrer($city_user) : null;

$donnees = [
    'account_name' => $account_name,
    'desc_name' => $desc_name,
    'work_user' => $work_user,
    'phone_user' => $phone_chiffre,
    'city_user' => $city_chiffree,
    'reg_visible' => $reg_visible,
    'id_corps_armee' => $id_corps_armee,
    'id_sous_corps_armee' => $id_sous_corps_armee,
    'id_situation' => $id_situation,
    'id_sous_situation' => $id_sous_situation,
];

if ($chemin_photo !== null) {
    $donnees['pictures_user'] = $chemin_photo;
}

try {
    $userRepo->mettreAJour($id_utilisateur, $donnees);
} catch (PDOException $e) {
    error_log('Erreur mise à jour profil : ' . $e->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue, merci de réessayer.');
}

header('Location: /profil?succes=1');
exit;