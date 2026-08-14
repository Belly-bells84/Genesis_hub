<?php

/**
 * Protection CSRF (Cross-Site Request Forgery).
 *
 * Principe : un jeton aléatoire est généré et stocké en session PHP
 * (côté serveur, invisible pour un attaquant). Ce même jeton est glissé
 * dans un champ caché de chaque formulaire sensible. Au moment du
 * traitement, on vérifie que le jeton reçu dans le POST correspond
 * bien à celui stocké en session.
 *
 * Un site extérieur qui tenterait de forger une requête vers GENESIS
 * (ex: un formulaire piégé sur un autre site) ne peut pas connaître
 * ce jeton — sa requête sera donc rejetée.
 */

/**
 * Génère le jeton CSRF s'il n'existe pas déjà en session,
 * et le retourne pour l'insérer dans le formulaire.
 * (on réutilise le même jeton tant que la session est active,
 * pas besoin d'en régénérer un à chaque affichage de page)
 */
function generer_jeton_csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Vérifie que le jeton reçu dans le formulaire correspond
 * à celui stocké en session.
 *
 * hash_equals() (et non ===) est utilisé volontairement : une simple
 * comparaison de chaînes s'arrête au premier caractère différent, ce
 * qui rend le temps de réponse légèrement variable selon où se situe
 * la différence — un attaquant pourrait exploiter ce micro-délai pour
 * deviner le jeton caractère par caractère ("timing attack").
 * hash_equals() compare toujours en temps constant, peu importe où
 * se situe la différence.
 */
function verifier_jeton_csrf(?string $jeton_recu): bool
{
    if (empty($_SESSION['csrf_token']) || empty($jeton_recu)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $jeton_recu);
}