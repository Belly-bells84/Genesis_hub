<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/chiffrement.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../models/class_user.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide, merci de recharger la page et de réessayer.');
}

$pdo = obtenir_connexion();
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

// Même validation des références qu'à l'inscription : on ne fait
// jamais confiance à un id envoyé par le navigateur.
$stmt_corps = $pdo->prepare('SELECT sous_corps_obligatoire FROM corps_armee WHERE id_corps_armee = ?');
$stmt_corps->execute([$id_corps_armee]);
$corps = $stmt_corps->fetch();

if (!$corps) {
    $erreurs[] = 'Corps d\'armée invalide.';
} else {
    if ((int) $corps['sous_corps_obligatoire'] === 1 && $id_sous_corps_armee === null) {
        $erreurs[] = 'Merci de préciser le sous-corps.';
    }

    if ($id_sous_corps_armee !== null) {
        $stmt_sous = $pdo->prepare('SELECT 1 FROM sous_corps_armee WHERE id_sous_corps_armee = ? AND id_corps_armee = ?');
        $stmt_sous->execute([$id_sous_corps_armee, $id_corps_armee]);
        if (!$stmt_sous->fetch()) {
            $erreurs[] = 'Sous-corps invalide pour ce corps d\'armée.';
        }
    }
}

$stmt_situation = $pdo->prepare('SELECT 1 FROM situation_relationship WHERE id_situation = ?');
$stmt_situation->execute([$id_situation]);
if (!$stmt_situation->fetch()) {
    $erreurs[] = 'Situation relationnelle invalide.';
}

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