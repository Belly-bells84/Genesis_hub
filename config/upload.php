<?php

/**
 * Valide et enregistre une photo de profil envoyée via $_FILES.
 *
 * Contrairement à une simple vérification d'extension, on contrôle ici
 * le VRAI contenu du fichier (via getimagesize, qui échoue proprement
 * sur autre chose qu'une image) et on ré-encode l'image avec GD.
 * Le ré-encodage a deux effets importants :
 *   - il supprime tout payload caché après les données d'image
 *     (fichiers "polyglots" : une image valide qui contient aussi du
 *     PHP ou du JS planqué à la fin du fichier) ;
 *   - il garantit que le fichier écrit sur le disque est bien une
 *     image, quel que soit ce que contenait le fichier envoyé.
 *
 * @throws UploadException si le fichier est invalide ou illisible
 * @return string|null Chemin public de la photo (ex: /asset/IMG/uploads/xxx.jpg), ou null si aucun fichier envoyé
 */
function traiter_upload_photo(array $fichier, string $repertoire_destination): ?string
{
    if (empty($fichier['name'])) {
        return null;
    }

    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        throw new UploadException('Échec de l\'envoi du fichier.');
    }

    if ($fichier['size'] > 5 * 1024 * 1024) { // 5 Mo max
        throw new UploadException('Photo trop volumineuse (5 Mo maximum).');
    }

    // On ne fait confiance ni à l'extension du nom de fichier, ni au
    // "Content-Type" envoyé par le navigateur (les deux sont falsifiables).
    // getimagesize() lit les octets réels du fichier pour déterminer si
    // c'est une vraie image, et renvoie false sinon.
    $infos_image = @getimagesize($fichier['tmp_name']);
    if ($infos_image === false) {
        throw new UploadException('Le fichier envoyé n\'est pas une image valide.');
    }

    $types_autorises = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    $type_image = $infos_image[2];
    if (!isset($types_autorises[$type_image])) {
        throw new UploadException('Format de photo non autorisé (jpg, png, webp uniquement).');
    }

    // Limite raisonnable de dimensions pour éviter les "image bombs"
    // (images aux dimensions énormes qui épuisent la mémoire au décodage)
    if ($infos_image[0] > 8000 || $infos_image[1] > 8000) {
        throw new UploadException('Dimensions de l\'image trop grandes.');
    }

    $extension = $types_autorises[$type_image];

    // Ré-encodage via GD : on recrée une image "propre" à partir des
    // pixels décodés, ce qui élimine tout contenu non-image caché dans
    // le fichier d'origine.
    $image_source = match ($type_image) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($fichier['tmp_name']),
        IMAGETYPE_PNG  => imagecreatefrompng($fichier['tmp_name']),
        IMAGETYPE_WEBP => imagecreatefromwebp($fichier['tmp_name']),
    };

    if ($image_source === false) {
        throw new UploadException('Impossible de lire l\'image envoyée.');
    }

    $nom_fichier = bin2hex(random_bytes(16)) . '.' . $extension;
    $chemin_destination = rtrim($repertoire_destination, '/') . '/' . $nom_fichier;

    $succes = match ($type_image) {
        IMAGETYPE_JPEG => imagejpeg($image_source, $chemin_destination, 85),
        IMAGETYPE_PNG  => imagepng($image_source, $chemin_destination, 6),
        IMAGETYPE_WEBP => imagewebp($image_source, $chemin_destination, 85),
    };

    imagedestroy($image_source);

    if (!$succes) {
        throw new UploadException('Échec de l\'enregistrement de la photo.');
    }

    return '/asset/IMG/uploads/' . $nom_fichier;
}

class UploadException extends RuntimeException
{
}