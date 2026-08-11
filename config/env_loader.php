<?php

/**
 * Lit un fichier .env et charge chaque variable via putenv().
 * Ignore les lignes vides et les commentaires (#).
 */
function charger_env(string $chemin_fichier): void
{
    if (!file_exists($chemin_fichier)) {
        throw new Exception("Fichier .env introuvable : $chemin_fichier");
    }

    $lignes = file($chemin_fichier, FILE_IGNORE_NEW_LINES);

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