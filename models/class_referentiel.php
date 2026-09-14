<?php

class Referentiel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function recupCorpsArmee(): array
    {
        return $this->pdo->query('SELECT id_corps_armee, libelle_corps_armee FROM corps_armee')->fetchAll();
    }

    public function recupSousCorpsArmee(): array
    {
        return $this->pdo->query('SELECT id_sous_corps_armee, libelle_sous_corps, id_corps_armee FROM sous_corps_armee')->fetchAll();
    }

    public function recupSituations(): array
    {
        return $this->pdo->query('SELECT id_situation, libelle_situation FROM situation_relationship')->fetchAll();
    }

    public function recupSousSituations(): array
    {
        return $this->pdo->query('SELECT id_sous_situation, libelle_sous_situation, id_situation FROM sous_situation')->fetchAll();
    }
}