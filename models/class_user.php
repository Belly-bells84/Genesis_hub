<?php

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function recupParEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, password_user, tentatives_echouees, bloque_jusqu_a
            FROM account_user
            WHERE email_user = ?
        ');
        $stmt->execute([$email]);
        $resultat = $stmt->fetch();

        return $resultat ?: null;
    }

    public function recupParId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT au.*, ca.libelle_corps_armee, sca.libelle_sous_corps,
                   sr.libelle_situation, ss.libelle_sous_situation
            FROM account_user au
            JOIN corps_armee ca ON ca.id_corps_armee = au.id_corps_armee
            LEFT JOIN sous_corps_armee sca ON sca.id_sous_corps_armee = au.id_sous_corps_armee
            JOIN situation_relationship sr ON sr.id_situation = au.id_situation
            LEFT JOIN sous_situation ss ON ss.id_sous_situation = au.id_sous_situation
            WHERE au.id = ?
        ');
        $stmt->execute([$id]);
        $resultat = $stmt->fetch();

        return $resultat ?: null;
    }

    public function emailExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM account_user WHERE email_user = ?');
        $stmt->execute([$email]);

        return (bool) $stmt->fetch();
    }

    /**
     * Crée un compte à partir d'un tableau associatif de colonnes => valeurs
     * déjà validées et préparées (mot de passe déjà hashé, champs sensibles
     * déjà chiffrés) par l'appelant. Cette méthode ne fait que l'insertion.
     */
    public function creer(array $donnees): int
    {
        $colonnes = array_keys($donnees);
        $placeholders = array_map(fn($colonne) => ":$colonne", $colonnes);

        $sql = 'INSERT INTO account_user (' . implode(', ', $colonnes) . ')
                VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($donnees);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour un compte à partir d'un tableau associatif de colonnes => valeurs.
     */
    public function mettreAJour(int $id, array $donnees): void
    {
        $assignations = implode(', ', array_map(fn($colonne) => "$colonne = :$colonne", array_keys($donnees)));

        $stmt = $this->pdo->prepare("UPDATE account_user SET $assignations WHERE id = :id");
        $stmt->execute([...$donnees, 'id' => $id]);
    }

    public function mettreAJourMotDePasse(int $id, string $nouveau_hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE account_user SET password_user = ? WHERE id = ?');
        $stmt->execute([$nouveau_hash, $id]);
    }

    public function reinitialiserTentatives(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE account_user SET tentatives_echouees = 0, bloque_jusqu_a = NULL WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function enregistrerEchecConnexion(int $id, int $nouvelles_tentatives): void
    {
        $stmt = $this->pdo->prepare('UPDATE account_user SET tentatives_echouees = ? WHERE id = ?');
        $stmt->execute([$nouvelles_tentatives, $id]);
    }

    public function bloquer(int $id, int $duree_minutes): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE account_user
            SET tentatives_echouees = 0, bloque_jusqu_a = DATE_ADD(NOW(), INTERVAL $duree_minutes MINUTE)
            WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    public function corpsArmeeExiste(int $id_corps_armee): ?array
    {
        $stmt = $this->pdo->prepare('SELECT sous_corps_obligatoire FROM corps_armee WHERE id_corps_armee = ?');
        $stmt->execute([$id_corps_armee]);
        $resultat = $stmt->fetch();

        return $resultat ?: null;
    }

    public function sousCorpsValide(int $id_sous_corps_armee, int $id_corps_armee): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM sous_corps_armee WHERE id_sous_corps_armee = ? AND id_corps_armee = ?');
        $stmt->execute([$id_sous_corps_armee, $id_corps_armee]);

        return (bool) $stmt->fetch();
    }

    public function situationExiste(int $id_situation): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM situation_relationship WHERE id_situation = ?');
        $stmt->execute([$id_situation]);

        return (bool) $stmt->fetch();
    }
}