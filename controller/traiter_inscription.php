<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/chiffrement.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/upload.php';
require_once __DIR__ . '/../models/class_user.php';
require_once __DIR__ . '/../models/valid_ref_profil.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

// Vérification CSRF en tout premier : si le jeton ne correspond pas,
// on arrête tout de suite, avant même de lire le reste du formulaire.
if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide, merci de recharger la page et de réessayer.');
}

$pdo = obtenir_connexion();
$userRepo = new User($pdo);
$erreurs = [];

// ============================================================
// 1. Récupération et validation des champs simples
// ============================================================
$account_name = trim($_POST['account_name'] ?? '');
$email_user = trim($_POST['email_user'] ?? '');
$password_user = $_POST['password_user'] ?? '';
$password_confirmation = $_POST['password_confirmation'] ?? '';
$date_birth_user = trim($_POST['date_birth_user'] ?? '');
$phone_user = trim($_POST['phone_user'] ?? '');
$city_user = trim($_POST['city_user'] ?? '');
$id_corps_armee = filter_input(INPUT_POST, 'id_corps_armee', FILTER_VALIDATE_INT);
$id_sous_corps_armee = filter_input(INPUT_POST, 'id_sous_corps_armee', FILTER_VALIDATE_INT) ?: null;
$id_situation = filter_input(INPUT_POST, 'id_situation', FILTER_VALIDATE_INT);
$celibat_geo = filter_input(INPUT_POST, 'celibat_geo', FILTER_VALIDATE_INT);

if ($account_name === '' || mb_strlen($account_name) > 150) {
    $erreurs[] = 'Pseudo invalide.';
}

if (!filter_var($email_user, FILTER_VALIDATE_EMAIL)) {
    $erreurs[] = 'Email invalide.';
}

if (mb_strlen($password_user) < 8) {
    $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
}

if ($password_user !== $password_confirmation) {
    $erreurs[] = 'Les mots de passe ne correspondent pas.';
}

// Point 3 : la majorité était calculée mais jamais vérifiée, ce qui
// permettait à n'importe quel âge de s'inscrire. On borne aussi la
// date basse (ex: 1900) pour écarter les dates farfelues.
$date_naissance = DateTime::createFromFormat('Y-m-d', $date_birth_user);
$date_minimum = DateTime::createFromFormat('Y-m-d', '1900-01-01');

if (!$date_naissance || $date_naissance > new DateTime() || $date_naissance < $date_minimum) {
    $erreurs[] = 'Date de naissance invalide.';
    $est_majeur = 0;
} else {
    $est_majeur = (new DateTime())->diff($date_naissance)->y >= 18 ? 1 : 0;
    if ($est_majeur === 0) {
        $erreurs[] = 'L\'inscription est réservée aux personnes majeures.';
    }
}

if ($celibat_geo === null || !in_array($celibat_geo, [0, 1], true)) {
    $erreurs[] = 'Merci de préciser votre situation géographique.';
}

// ============================================================
// 2. Validation des références (corps d'armée, sous-corps, situation)
//    Factorisée dans models/valider_reference_profil.php (point 5)
// ============================================================
valid_ref_profil($pdo, $id_corps_armee, $id_sous_corps_armee, $id_situation, $erreurs);

// ============================================================
// 3. Arrêt si erreurs de validation détectées jusqu'ici
// ============================================================
// Remarque sur l'unicité de l'email (point 2) : on a RETIRÉ la
// vérification "SELECT ... WHERE email_user = ?" faite ici avant
// l'INSERT. Ce n'était pas suffisant : deux inscriptions envoyées en
// même temps avec le même email pouvaient toutes les deux passer ce
// SELECT avant qu'aucune n'ait encore fait l'INSERT (race condition).
// La vraie garantie doit venir d'une contrainte UNIQUE en base sur
// email_user (voir migration SQL fournie), et c'est l'INSERT
// lui-même, plus bas, qui échouera proprement si l'email existe déjà.
if (!empty($erreurs)) {
    http_response_code(422);
    foreach ($erreurs as $erreur) {
        echo '<p>' . htmlspecialchars($erreur) . '</p>';
    }
    exit;
}

// ============================================================
// 4. Photo de profil (facultative) — point 1 : upload sécurisé
// ============================================================
try {
    $chemin_photo = traiter_upload_photo(
        $_FILES['pictures_user'] ?? [],
        __DIR__ . '/../asset/IMG/uploads'
    );
} catch (UploadException $e) {
    http_response_code(422);
    exit(htmlspecialchars($e->getMessage()));
}

// ============================================================
// 5. Hash du mot de passe, chiffrement des données sensibles
// ============================================================
$password_hash = password_hash($password_user, PASSWORD_BCRYPT);
$phone_chiffre = $phone_user !== '' ? chiffrer($phone_user) : null;
$city_chiffree = $city_user !== '' ? chiffrer($city_user) : null;
$date_birth_chiffree = chiffrer($date_birth_user);

// ============================================================
// 6. Insertion en base
// ============================================================
try {
    $userRepo->creer([
        'pictures_user' => $chemin_photo,
        'account_name' => $account_name,
        'email_user' => $email_user,
        'password_user' => $password_hash,
        'date_birth_user' => $date_birth_chiffree,
        'phone_user' => $phone_chiffre,
        'city_user' => $city_chiffree,
        'celibat_geo' => $celibat_geo,
        'est_majeur' => $est_majeur,
        'account_valid' => 0,
        'reg_visible' => 1,
        'theme' => 'feminin',
        'id_corps_armee' => $id_corps_armee,
        'id_sous_corps_armee' => $id_sous_corps_armee,
        'id_situation' => $id_situation,
    ]);
} catch (PDOException $e) {
    // Code MySQL 1062 = violation de contrainte UNIQUE (email déjà pris).
    // C'est ce filet de sécurité en base, et non un SELECT préalable,
    // qui garantit vraiment l'unicité même en cas de requêtes simultanées.
    if ((int) $e->errorInfo[1] === 1062) {
        http_response_code(422);
        exit('<p>Un compte existe déjà avec cet email.</p>');
    }

    error_log('Erreur inscription : ' . $e->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue, merci de réessayer.');
}

header('Location: /connexion?inscription=succes');
exit;