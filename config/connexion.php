<?php

require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/config.php';


function obtenir_connexion(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv('DB_HOST');
    $nom_base = getenv('DB_NAME');
    $utilisateur = getenv('DB_USER');
    $mot_de_passe = getenv('DB_PASSWORD');

    $dsn = "mysql:host=$host;dbname=$nom_base;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $utilisateur, $mot_de_passe, $options);
    } catch (PDOException $e) {
        error_log('Erreur de connexion BDD : ' . $e->getMessage());
        throw new Exception('Impossible de se connecter à la base de données.');
    }

    return $pdo;
}