<?php

/**
 * Retourne true si une utilisatrice est actuellement connectée.
 */
function utilisateur_connecte(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Bloque l'accès à la page si personne n'est connecté :
 * redirige vers /connexion et arrête immédiatement l'exécution.
 * À appeler tout en haut de chaque page protégée, avant d'afficher
 * quoi que ce soit (comme pour header('Location: ...'), doit être
 * appelé avant toute sortie HTML).
 */
function exiger_connexion(): void
{
    if (!utilisateur_connecte()) {
        header('Location: /connexion');
        exit;
    }
}