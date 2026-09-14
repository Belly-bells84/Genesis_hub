<?php

require_once __DIR__ . '/../models/class_publication.php';
require_once __DIR__ . '/../models/class_message.php';

/**
 * Crée une base SQLite en mémoire avec un schéma équivalent aux tables
 * MySQL réelles (colonnes identiques), pour tester les classes Publication
 * et MessagePrivate sans dépendre d'un serveur MySQL. Les deux classes
 * étant conçues pour recevoir un PDO déjà connecté (injection de
 * dépendance), elles sont testables telles quelles, sans modification.
 */
function creer_pdo_test(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec('
        CREATE TABLE account_user (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            account_name TEXT NOT NULL
        );
    ');

    $pdo->exec('
        CREATE TABLE publication (
            id_publication INTEGER PRIMARY KEY AUTOINCREMENT,
            date_creation_publication TEXT DEFAULT CURRENT_TIMESTAMP,
            contenu_publication TEXT,
            chemin_media TEXT,
            type_media TEXT
        );
    ');

    $pdo->exec('
        CREATE TABLE rediger_publication (
            id_user INTEGER NOT NULL,
            id_publication INTEGER NOT NULL,
            PRIMARY KEY (id_user, id_publication)
        );
    ');

    $pdo->exec('
        CREATE TABLE aimer_publication (
            id_user INTEGER NOT NULL,
            id_publication INTEGER NOT NULL,
            PRIMARY KEY (id_user, id_publication)
        );
    ');

    $pdo->exec('
        CREATE TABLE commentaire (
            id_commentaire INTEGER PRIMARY KEY AUTOINCREMENT,
            date_creation_commentaire TEXT DEFAULT CURRENT_TIMESTAMP,
            contenu_commentaire TEXT NOT NULL,
            id_publication INTEGER NOT NULL
        );
    ');

    $pdo->exec('
        CREATE TABLE rediger_commentaire (
            id_user INTEGER NOT NULL,
            id_commentaire INTEGER NOT NULL,
            PRIMARY KEY (id_user, id_commentaire)
        );
    ');

    $pdo->exec('
        CREATE TABLE message_private (
            id_message_private INTEGER PRIMARY KEY AUTOINCREMENT,
            account_user_emetteur INTEGER NOT NULL,
            account_user_destinataire INTEGER NOT NULL,
            contenu_message TEXT NOT NULL,
            date_envoi_message TEXT DEFAULT CURRENT_TIMESTAMP,
            message_lu INTEGER NOT NULL DEFAULT 0
        );
    ');

    return $pdo;
}