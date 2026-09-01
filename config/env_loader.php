<?php

/**
 * Lit un fichier .env et charge chaque variable via putenv().
 * Ignore les lignes vides et les commentaires (#).
 *
 * Ne définit une variable que si elle n'est pas déjà présente dans
 * l'environnement réel du processus (getenv() la trouve déjà) — ça
 * permet à un conteneur (Podman/Docker) de fournir DB_HOST, DB_USER,
 * etc. directement via son "environment:", sans que le fichier .env
 * embarqué dans l'image ne les écrase. En local sous WAMP, où ces
 * variables n'existent pas dans l'environnement du processus PHP,
 * le comportement est inchangé : les valeurs viennent bien du fichier.
 */
function charger_env(string $chemin_fichier): void
{
    if (!file_exists($chemin_fichier)) {
        throw new Exception("Fichier .env introuvable : $chemin_fichier");
    }

    $lignes = file($chemin_fichier, FILE_IGNORE_NEW_LINES);

    // Retire un éventuel BOM UTF-8 (3 octets invisibles ajoutés par certains
    // éditeurs/terminaux Windows en tout début de fichier), qui empêcherait
    // sinon de reconnaître la toute première clé (ex: "APP_ENV")
    if (isset($lignes[0])) {
        $lignes[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lignes[0]);
    }

    foreach ($lignes as $ligne) {
        $ligne = trim($ligne);

        if ($ligne === '' || str_starts_with($ligne, '#')) {
            continue;
        }

        $parts = explode('=', $ligne, 2);

        if (count($parts) < 2) {
            continue;
        }

        $cle    = trim($parts[0]);
        $valeur = trim($parts[1]);

        // Priorité à une variable déjà définie dans l'environnement réel
        // (ex: injectée par podman-compose) sur celle du fichier .env.
        if (getenv($cle) !== false) {
            continue;
        }

        putenv("$cle=$valeur");
    }
}

// 1. Charger le fichier pilote .env pour connaître l'environnement actif
charger_env(__DIR__ . '/../.env');

$environnements_valides = ['dev', 'prod'];
$environnement = getenv('APP_ENV');

if (!in_array($environnement, $environnements_valides, true)) {
    throw new Exception("APP_ENV invalide ou manquant dans .env (attendu : dev ou prod)");
}

// 2. Charger le fichier spécifique à l'environnement (.env.dev ou .env.prod)
charger_env(__DIR__ . '/../.env.' . $environnement);