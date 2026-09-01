<?php

/**
 * Exception de base de l'application.
 *
 * Toutes les exceptions "métier" (page introuvable, accès refusé, etc.)
 * héritent de celle-ci. Elle porte un code HTTP à renvoyer au navigateur,
 * ce qui évite de dupliquer http_response_code() un peu partout dans le
 * routeur : c'est le bloc catch, dans index.php, qui s'en charge une
 * fois pour toutes.
 */
class AppException extends Exception
{
    private int $codeHttp;

    public function __construct(string $message, int $codeHttp = 500)
    {
        parent::__construct($message);
        $this->codeHttp = $codeHttp;
    }

    public function getCodeHttp(): int
    {
        return $this->codeHttp;
    }
}