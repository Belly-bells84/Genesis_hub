<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/chiffrement.php';
require_once __DIR__ . '/../config/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

// Vérification CSRF en tout premier : si le jeton ne correspond pas,
// on arrête tout de suite, avant même de lire le reste du formulaire.
// (session_start() a déjà été appelé dans index.php, donc $_SESSION
// est disponible ici aussi, puisque ce fichier est inclus par index.php)
if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide, merci de recharger la page et de réessayer.');
}

$pdo = obtenir_connexion();
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

$date_naissance = DateTime::createFromFormat('Y-m-d', $date_birth_user);
if (!$date_naissance || $date_naissance > new DateTime()) {
    $erreurs[] = 'Date de naissance invalide.';
}

if ($celibat_geo === null || !in_array($celibat_geo, [0, 1], true)) {
    $erreurs[] = 'Merci de préciser votre situation géographique.';
}

// ============================================================
// 2. Validation des références (corps d'armée, sous-corps, situation)
//    On ne fait jamais confiance à un id envoyé par le navigateur :
//    on vérifie qu'il existe réellement en base avant de l'utiliser.
// ============================================================
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

// ============================================================
// 3. Vérification d'unicité de l'email
// ============================================================
if (empty($erreurs)) {
    $stmt_email = $pdo->prepare('SELECT 1 FROM account_user WHERE email_user = ?');
    $stmt_email->execute([$email_user]);
    if ($stmt_email->fetch()) {
        $erreurs[] = 'Un compte existe déjà avec cet email.';
    }
}

// ============================================================
// 4. Arrêt si erreurs de validation
// ============================================================
if (!empty($erreurs)) {
    http_response_code(422);
    foreach ($erreurs as $erreur) {
        echo '<p>' . htmlspecialchars($erreur) . '</p>';
    }
    exit;
}

// ============================================================
// 5. Photo de profil (facultative)
// ============================================================
$chemin_photo = null;

if (!empty($_FILES['pictures_user']['name'])) {
    $extensions_autorisees = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($_FILES['pictures_user']['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensions_autorisees, true)) {
        http_response_code(422);
        exit('Format de photo non autorisé.');
    }

    if ($_FILES['pictures_user']['size'] > 5 * 1024 * 1024) { // 5 Mo max
        http_response_code(422);
        exit('Photo trop volumineuse (5 Mo maximum).');
    }

    // Nom de fichier généré aléatoirement : on ne fait jamais confiance
    // au nom de fichier envoyé par le navigateur
    $nom_fichier = bin2hex(random_bytes(16)) . '.' . $extension;
    $chemin_destination = __DIR__ . '/../asset/IMG/uploads/' . $nom_fichier;

    if (!move_uploaded_file($_FILES['pictures_user']['tmp_name'], $chemin_destination)) {
        http_response_code(500);
        exit('Échec de l\'envoi de la photo.');
    }

    $chemin_photo = '/asset/IMG/uploads/' . $nom_fichier;
}

// ============================================================
// 6. Hash du mot de passe, chiffrement des données sensibles
// ============================================================
$password_hash = password_hash($password_user, PASSWORD_BCRYPT);
$phone_chiffre = $phone_user !== '' ? chiffrer($phone_user) : null;
$city_chiffree = $city_user !== '' ? chiffrer($city_user) : null;
$date_birth_chiffree = chiffrer($date_birth_user);

$est_majeur = (new DateTime())->diff($date_naissance)->y >= 18 ? 1 : 0;

// ============================================================
// 7. Insertion en base
// ============================================================
try {
    $stmt = $pdo->prepare('
        INSERT INTO account_user (
            pictures_user, account_name, email_user, password_user,
            date_birth_user, phone_user, city_user, celibat_geo,
            est_majeur, account_valid, reg_visible, theme,
            id_corps_armee, id_sous_corps_armee, id_situation
        ) VALUES (
            :pictures_user, :account_name, :email_user, :password_user,
            :date_birth_user, :phone_user, :city_user, :celibat_geo,
            :est_majeur, :account_valid, :reg_visible, :theme,
            :id_corps_armee, :id_sous_corps_armee, :id_situation
        )
    ');

    $stmt->execute([
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
    error_log('Erreur inscription : ' . $e->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue, merci de réessayer.');
}

header('Location: /connexion?inscription=succes');
exit;