<?php

require_once __DIR__ . '/env_loader.php';

const CHIFFREMENT_METHODE = 'aes-256-gcm';

/**
 * Chiffre une valeur avec la clé APP_KEY (AES-256-GCM).
 * Retourne une chaîne encodée en base64, contenant l'IV + le tag + le texte chiffré.
 */
function chiffrer(string $valeur_en_clair): string
{
    $cle = hex2bin(getenv('APP_KEY'));
    $taille_iv = openssl_cipher_iv_length(CHIFFREMENT_METHODE);
    $iv = random_bytes($taille_iv);

    $tag = '';
    $texte_chiffre = openssl_encrypt(
        $valeur_en_clair,
        CHIFFREMENT_METHODE,
        $cle,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($texte_chiffre === false) {
        throw new Exception('Échec du chiffrement');
    }

    // On stocke IV + tag + texte chiffré ensemble, pour pouvoir tout redécouper au déchiffrement
    return base64_encode($iv . $tag . $texte_chiffre);
}

/**
 * Déchiffre une valeur produite par chiffrer().
 */
function dechiffrer(string $valeur_chiffree): string
{
    $cle = hex2bin(getenv('APP_KEY'));
    $donnees = base64_decode($valeur_chiffree);

    $taille_iv = openssl_cipher_iv_length(CHIFFREMENT_METHODE);
    $taille_tag = 16;

    $iv = substr($donnees, 0, $taille_iv);
    $tag = substr($donnees, $taille_iv, $taille_tag);
    $texte_chiffre = substr($donnees, $taille_iv + $taille_tag);

    $valeur_en_clair = openssl_decrypt(
        $texte_chiffre,
        CHIFFREMENT_METHODE,
        $cle,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($valeur_en_clair === false) {
        throw new Exception('Échec du déchiffrement (donnée corrompue ou mauvaise clé)');
    }

    return $valeur_en_clair;
}