<?php

require_once __DIR__ . '/App_exception.php';

/**
 * Levée quand $page ne correspond à aucune route connue du routeur.
 * Remplace l'ancien "default: http_response_code(404); echo ...".
 */
class PageIntrouvableException extends AppException
{
    public function __construct(string $route_demandee)
    {
        parent::__construct("Route inconnue : $route_demandee", 404);
    }
}