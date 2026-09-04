<?php

class MessagePrivate
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Vérifie qu'un utilisateur existe (pour valider un id_destinataire
     * ou un id_contact venu de l'URL/POST avant de l'utiliser).
     */
    public function utilisateurExiste(int $id_utilisateur): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM account_user WHERE id = ?');
        $stmt->execute([$id_utilisateur]);

        return (bool) $stmt->fetch();
    }

    /**
     * Récupère les infos d'affichage d'un utilisateur (pour l'en-tête
     * de la page de conversation).
     */
    public function recupUtilisateur(int $id_utilisateur): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, account_name FROM account_user WHERE id = ?');
        $stmt->execute([$id_utilisateur]);
        $resultat = $stmt->fetch();

        return $resultat !== false ? $resultat : null;
    }

    /**
     * Liste les conversations de $id_utilisateur : une ligne par
     * interlocutrice, avec son dernier message et son nombre de
     * messages non lus. Triée par date du dernier message (récent
     * en premier), comme une liste de conversations Messenger.
     *
     * Le sous-select "contacts" identifie chaque interlocutrice via
     * un CASE (destinataire si c'est moi qui ai envoyé, émetteur sinon),
     * et prend le MAX(id_message_private) comme dernier message de
     * cette conversation — les ids étant auto-incrémentés dans l'ordre
     * chronologique d'envoi.
     */
    public function recupConversations(int $id_utilisateur): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                autre.id AS id_contact,
                autre.account_name,
                dernier.contenu_message AS dernier_message,
                dernier.date_envoi_message,
                dernier.account_user_emetteur AS dernier_emetteur,
                (
                    SELECT COUNT(*) FROM message_private mp
                    WHERE mp.account_user_emetteur = autre.id
                      AND mp.account_user_destinataire = :id_utilisateur1
                      AND mp.message_lu = 0
                ) AS nb_non_lus
            FROM (
                SELECT
                    CASE
                        WHEN account_user_emetteur = :id_utilisateur2 THEN account_user_destinataire
                        ELSE account_user_emetteur
                    END AS id_contact,
                    MAX(id_message_private) AS id_dernier_message
                FROM message_private
                WHERE account_user_emetteur = :id_utilisateur3
                   OR account_user_destinataire = :id_utilisateur4
                GROUP BY id_contact
            ) AS contacts
            INNER JOIN account_user autre ON autre.id = contacts.id_contact
            INNER JOIN message_private dernier ON dernier.id_message_private = contacts.id_dernier_message
            ORDER BY dernier.date_envoi_message DESC
        ');
        $stmt->execute([
            'id_utilisateur1' => $id_utilisateur,
            'id_utilisateur2' => $id_utilisateur,
            'id_utilisateur3' => $id_utilisateur,
            'id_utilisateur4' => $id_utilisateur,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Récupère les messages échangés entre $id_utilisateur et $id_contact.
     * Si $depuis_id est fourni, ne retourne que les messages plus récents
     * que cet id (utilisé pour le polling : "y a-t-il du nouveau ?").
     */
    public function recupMessages(int $id_utilisateur, int $id_contact, ?int $depuis_id = null): array
    {
        $sql = '
            SELECT id_message_private, account_user_emetteur, account_user_destinataire,
                   contenu_message, date_envoi_message, message_lu
            FROM message_private
            WHERE (
                (account_user_emetteur = :id_utilisateur1 AND account_user_destinataire = :id_contact1)
                OR (account_user_emetteur = :id_contact2 AND account_user_destinataire = :id_utilisateur2)
            )
        ';

        $params = [
            'id_utilisateur1' => $id_utilisateur,
            'id_contact1' => $id_contact,
            'id_contact2' => $id_contact,
            'id_utilisateur2' => $id_utilisateur,
        ];

        if ($depuis_id !== null) {
            $sql .= ' AND id_message_private > :depuis_id';
            $params['depuis_id'] = $depuis_id;
        }

        $sql .= ' ORDER BY id_message_private ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Envoie un message et retourne son id, pour pouvoir ensuite
     * construire la réponse JSON envoyée au JS.
     */
    public function envoyerMessage(int $id_emetteur, int $id_destinataire, string $contenu_message): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO message_private (account_user_emetteur, account_user_destinataire, contenu_message)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$id_emetteur, $id_destinataire, $contenu_message]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Marque comme lus tous les messages envoyés par $id_contact à
     * $id_utilisateur. Appelée à l'ouverture d'une conversation et à
     * chaque cycle de polling.
     */
    public function marquerCommeLu(int $id_utilisateur, int $id_contact): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE message_private
            SET message_lu = 1
            WHERE account_user_emetteur = ? AND account_user_destinataire = ? AND message_lu = 0
        ');
        $stmt->execute([$id_contact, $id_utilisateur]);
    }

    /**
     * Recherche des utilisatrices par nom (pour démarrer une nouvelle
     * conversation), en excluant l'utilisatrice courante des résultats.
     */
    public function rechercherUtilisateurs(string $terme, int $id_utilisateur_courant): array
    {
        // Note : on n'exclut plus l'utilisatrice courante des résultats,
        // pour permettre de s'envoyer un message à soi-même (bloc-notes
        // personnel, tests, etc.). $id_utilisateur_courant n'est donc
        // plus utilisé ici, mais on garde le paramètre pour ne pas
        // casser les appels existants et pour une éventuelle réutilisation.
        $stmt = $this->pdo->prepare('
            SELECT id, account_name
            FROM account_user
            WHERE account_name LIKE :terme
            ORDER BY account_name ASC
            LIMIT 10
        ');
        $stmt->execute([
            'terme' => '%' . $terme . '%',
        ]);

        return $stmt->fetchAll();
    }
}