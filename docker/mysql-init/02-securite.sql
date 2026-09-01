-- Point 2 : contrainte UNIQUE sur l'email.
-- C'est elle, et non plus le SELECT préalable dans le code PHP, qui
-- garantit vraiment qu'on ne peut pas créer deux comptes avec le même
-- email, même en cas de requêtes d'inscription simultanées.
--
-- ⚠️ Avant d'exécuter cette ligne, vérifiez qu'il n'existe pas déjà de
-- doublons en base, sinon la commande échouera :
--   SELECT email_user, COUNT(*) FROM account_user GROUP BY email_user HAVING COUNT(*) > 1;
ALTER TABLE account_user
    ADD CONSTRAINT uniq_email_user UNIQUE (email_user);

-- Point 6 : table de suivi des tentatives de connexion par IP,
-- pour le throttling anti credential-stuffing dans traiter_connexion.php
CREATE TABLE IF NOT EXISTS tentatives_connexion_ip (
    ip VARCHAR(45) NOT NULL PRIMARY KEY,  -- 45 = longueur max d'une IPv6
    tentatives INT UNSIGNED NOT NULL DEFAULT 0,
    bloque_jusqu_a DATETIME NULL,
    derniere_tentative TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recommandé : purge périodique des vieilles entrées (via une tâche cron,
-- par exemple une fois par jour), pour que la table ne grossisse pas indéfiniment
-- DELETE FROM tentatives_connexion_ip WHERE derniere_tentative < DATE_SUB(NOW(), INTERVAL 7 DAY);