<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/chiffrement.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/upload.php';
require_once __DIR__ . '/../models/class_user.php';
require_once __DIR__ . '/../models/valid_ref_profil.php';
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide, merci de recharger la page et de réessayer.');
}

$pdo = obtenir_connexion();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Vous devez être connecté.');
}
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
$celibat_geo = filter_input(INPUT_POST, 'celibat_geo', FILTER_VALIDATE_INT);
$reg_visible = isset($_POST['reg_visible']) ? 1 : 0;

if ($account_name === '' || mb_strlen($account_name) > 150) {
    $erreurs[] = 'Pseudo invalide.';
}

if ($celibat_geo === null || !in_array($celibat_geo, [0, 1], true)) {
    $erreurs[] = 'Merci de préciser votre situation géographique.';
}

// Point 5 : même validation qu'à l'inscription, désormais factorisée
// dans models/valider_reference_profil.php pour ne plus avoir deux
// copies de cette logique à maintenir en parallèle.
valid_ref_profil($pdo, $id_corps_armee, $id_sous_corps_armee, $id_situation, $erreurs);

if (!empty($erreurs)) {
    http_response_code(422);
    foreach ($erreurs as $erreur) {
        echo '<p>' . htmlspecialchars($erreur) . '</p>';
    }
    exit;
}

// Point 1 : upload sécurisé (vérifie le vrai contenu, ré-encode l'image)
try {
    $chemin_photo = traiter_upload_photo(
        $_FILES['pictures_user'] ?? [],
        __DIR__ . '/../asset/IMG/uploads'
    );
} catch (UploadException $e) {
    http_response_code(422);
    exit(htmlspecialchars($e->getMessage()));
}

$phone_chiffre = $phone_user !== '' ? chiffrer($phone_user) : null;
$city_chiffree = $city_user !== '' ? chiffrer($city_user) : null;

$champs_sql = '
    account_name = :account_name,
    desc_name = :desc_name,
    work_user = :work_user,
    phone_user = :phone_user,
    city_user = :city_user,
    celibat_geo = :celibat_geo,
    reg_visible = :reg_visible,
    id_corps_armee = :id_corps_armee,
    id_sous_corps_armee = :id_sous_corps_armee,
    id_situation = :id_situation
';

$parametres = [
    'account_name' => $account_name,
    'desc_name' => $desc_name,
    'work_user' => $work_user,
    'phone_user' => $phone_chiffre,
    'city_user' => $city_chiffree,
    'celibat_geo' => $celibat_geo,
    'reg_visible' => $reg_visible,
    'id_corps_armee' => $id_corps_armee,
    'id_sous_corps_armee' => $id_sous_corps_armee,
    'id_situation' => $id_situation,
    'id' => $id_utilisateur,
];

if ($chemin_photo !== null) {
    $champs_sql .= ', pictures_user = :pictures_user';
    $parametres['pictures_user'] = $chemin_photo;
}

try {
    $stmt = $pdo->prepare("UPDATE account_user SET $champs_sql WHERE id = :id");
    $stmt->execute($parametres);
} catch (PDOException $e) {
    error_log('Erreur mise à jour profil : ' . $e->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue, merci de réessayer.');
}

header('Location: /profil?succes=1');
exit;