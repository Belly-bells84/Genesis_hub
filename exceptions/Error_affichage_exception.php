<?php

require_once __DIR__ . '/App_exception.php';

/**
 * Levée quand une vue attendue ne peut pas être incluse (fichier manquant,
 * déplacé, etc.) — distincte d'un 404 : la route existe et est reconnue,
 * mais son rendu échoue pour une raison technique.
 */
class ErreurAffichageException extends AppException
{
    public function __construct(string $page, string $chemin_vue)
    {
        parent::__construct("Impossible d'afficher la page '$page' : vue introuvable ($chemin_vue)", 500);
    }
}