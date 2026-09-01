<?php

class Publication
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère toutes les publications avec leur auteure, le nombre de
     * likes, si $id_utilisateur les a déjà aimées, et le nombre de
     * commentaires. $id_utilisateur est un paramètre de la méthode car
     * "quel utilisateur consulte le feed" change à chaque appel — ce
     * n'est pas une donnée fixe de la classe comme $pdo.
     */
    public function recup_publication_all(int $id_utilisateur): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                p.id_publication,
                p.date_creation_publication,
                p.contenu_publication,
                p.chemin_media,
                p.type_media,
                au.account_name,
                (SELECT COUNT(*) FROM aimer_publication ap WHERE ap.id_publication = p.id_publication) AS nb_likes,
                EXISTS(
                    SELECT 1 FROM aimer_publication ap2
                    WHERE ap2.id_publication = p.id_publication AND ap2.id_user = :id_utilisateur
                ) AS deja_aime,
                (SELECT COUNT(*) FROM commentaire c WHERE c.id_publication = p.id_publication) AS nb_commentaires
            FROM publication p
            INNER JOIN rediger_publication rp ON p.id_publication = rp.id_publication
            INNER JOIN account_user au ON au.id = rp.id_user
            ORDER BY p.date_creation_publication DESC
            ');
        $stmt->execute(['id_utilisateur' => $id_utilisateur]);

        return $stmt->fetchAll();
    }

    /**
     * Récupère les commentaires de plusieurs publications à la fois,
     * regroupés par id_publication (même logique que dans page_feed.php).
     */
    public function recup_commentaires_par_publications(array $ids_publications): array
    {
        if (empty($ids_publications)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids_publications), '?'));

        $stmt = $this->pdo->prepare("
            SELECT c.id_publication, c.contenu_commentaire, c.date_creation_commentaire, au.account_name
            FROM commentaire c
            INNER JOIN rediger_commentaire rc ON rc.id_commentaire = c.id_commentaire
            INNER JOIN account_user au ON au.id = rc.id_user
            WHERE c.id_publication IN ($placeholders)
            ORDER BY c.date_creation_commentaire ASC
        ");
        $stmt->execute($ids_publications);

        $commentaires_par_publication = [];
        foreach ($stmt->fetchAll() as $commentaire) {
            $commentaires_par_publication[$commentaire['id_publication']][] = $commentaire;
        }

        return $commentaires_par_publication;
    }

    /**
     * Crée une publication + son lien vers l'auteure, en transaction.
     */
    /**
 * Crée une publication + son lien vers l'auteure, en transaction.
 * $chemin_media et $type_media sont optionnels (null si publication texte seul).
 */
    public function creer(
        int $id_utilisateur,
        string $contenu_publication,
        ?string $chemin_media = null,
        ?string $type_media = null
        ): void {
            $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO publication (contenu_publication, chemin_media, type_media) VALUES (?, ?, ?)'
            );
            $stmt->execute([$contenu_publication, $chemin_media, $type_media]);
            $id_publication = (int) $this->pdo->lastInsertId();

            $stmt_lien = $this->pdo->prepare('INSERT INTO rediger_publication (id_user, id_publication) VALUES (?, ?)');
            $stmt_lien->execute([$id_utilisateur, $id_publication]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Bascule le like d'une publication (l'ajoute si absent, le retire si présent).
     */
    public function basculerLike(int $id_utilisateur, int $id_publication): void
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM aimer_publication WHERE id_user = ? AND id_publication = ?');
        $stmt->execute([$id_utilisateur, $id_publication]);

        if ($stmt->fetch()) {
            $stmt = $this->pdo->prepare('DELETE FROM aimer_publication WHERE id_user = ? AND id_publication = ?');
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO aimer_publication (id_user, id_publication) VALUES (?, ?)');
        }

        $stmt->execute([$id_utilisateur, $id_publication]);
    }

    public function existe(int $id_publication): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM publication WHERE id_publication = ?');
        $stmt->execute([$id_publication]);

        return (bool) $stmt->fetch();
    }

    /**
     * Crée un commentaire + son lien vers l'auteure et vers sa publication, en transaction.
     */
    public function creerCommentaire(int $id_utilisateur, int $id_publication, string $contenu_commentaire): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('INSERT INTO commentaire (contenu_commentaire, id_publication) VALUES (?, ?)');
            $stmt->execute([$contenu_commentaire, $id_publication]);
            $id_commentaire = (int) $this->pdo->lastInsertId();

            $stmt_lien = $this->pdo->prepare('INSERT INTO rediger_commentaire (id_user, id_commentaire) VALUES (?, ?)');
            $stmt_lien->execute([$id_utilisateur, $id_commentaire]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}