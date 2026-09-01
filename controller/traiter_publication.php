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
$contenu_publication = trim($_POST['contenu_publication'] ?? '');

// Une publication doit avoir du texte OU un média (pas obligatoirement les deux).
$a_un_fichier = isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE;

if ($contenu_publication === '' && !$a_un_fichier) {
    http_response_code(422);
    exit('Une publication doit contenir du texte ou un média.');
}

if ($contenu_publication !== '' && mb_strlen($contenu_publication) > 1500) {
    http_response_code(422);
    exit('Contenu de publication invalide.');
}

// ============================================================
// Traitement du média (optionnel)
// ============================================================
$chemin_media = null;
$type_media = null;

if ($a_un_fichier) {
    $fichier = $_FILES['media'];

    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        http_response_code(422);
        exit('Erreur lors de l\'envoi du fichier.');
    }

    // Types autorisés : extension => [mime attendu, dossier, type_media, taille max en octets]
    $types_autorises = [
        'image/jpeg' => ['jpg', 5 * 1024 * 1024],
        'image/png'  => ['png', 5 * 1024 * 1024],
        'image/webp' => ['webp', 5 * 1024 * 1024],
        'video/mp4'  => ['mp4', 50 * 1024 * 1024],
        'video/webm' => ['webm', 50 * 1024 * 1024],
    ];

    // On vérifie le VRAI type du fichier (finfo lit les octets du fichier,
    // pas le nom ni le Content-Type envoyé par le navigateur, facilement falsifiables).
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    // Re-encoder l'image force la suppression de tout code caché
    if ($type_media === 'image') {
    $image = imagecreatefromstring(file_get_contents($chemin_destination));
    imagejpeg($image, $chemin_destination, 85); // ou imagepng/imagewebp selon le format
    imagedestroy($image);
    }
    $mime_reel = $finfo->file($fichier['tmp_name']);

    if (!isset($types_autorises[$mime_reel])) {
        http_response_code(422);
        exit('Type de fichier non autorisé. Formats acceptés : JPG, PNG, WEBP, MP4, WEBM.');
    }

    [$extension, $taille_max] = $types_autorises[$mime_reel];

    if ($fichier['size'] > $taille_max) {
        http_response_code(422);
        exit('Fichier trop volumineux.');
    }

    // Nom de fichier généré côté serveur : jamais le nom original de l'utilisatrice.
    $nom_fichier = bin2hex(random_bytes(16)) . '.' . $extension;
    $dossier_destination = __DIR__ . '/../uploads/media/';
    $chemin_destination = $dossier_destination . $nom_fichier;

    if (!move_uploaded_file($fichier['tmp_name'], $chemin_destination)) {
        error_log('Échec du déplacement du fichier uploadé vers ' . $chemin_destination);
        http_response_code(500);
        exit('Une erreur est survenue lors de l\'enregistrement du fichier.');
    }

    $chemin_media = '/uploads/media/' . $nom_fichier;
    $type_media = str_starts_with($mime_reel, 'image/') ? 'image' : 'video';
}

// ============================================================
// Enregistrement en base
// ============================================================
try {
    $publicationRepo->creer($id_utilisateur, $contenu_publication, $chemin_media, $type_media);
} catch (PDOException $e) {
    error_log('Erreur création publication : ' . $e->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue, merci de réessayer.');
}

header('Location: /page_feed.php');
exit;