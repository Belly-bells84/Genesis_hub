-- ============================================================
-- GENESIS - Script de création de base de données (MPD)
-- Périmètre : compte utilisateur, feed, messagerie, profil
-- Moteur : MariaDB / MySQL
-- ============================================================

CREATE TABLE corps_armee (
    id_corps_armee      INT AUTO_INCREMENT PRIMARY KEY,
    libelle_corps_armee VARCHAR(150) NOT NULL
);

CREATE TABLE situation_relationship (
    id_situation    INT AUTO_INCREMENT PRIMARY KEY,
    libelle_situation VARCHAR(150) NOT NULL
);

CREATE TABLE administrateur (
    id_admin    INT AUTO_INCREMENT PRIMARY KEY,
    work_admin  VARCHAR(150)
);

-- ============================================================
CREATE TABLE account_user (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    pictures_user           VARCHAR(150),
    account_name            VARCHAR(150) NOT NULL,
    desc_name               VARCHAR(1500),
    city_user               VARCHAR(50),
    reg_partenaire_count    VARCHAR(50),
    email_user              VARCHAR(255) NOT NULL UNIQUE,
    password_user           VARCHAR(255) NOT NULL,
    date_birth_user         DATE NOT NULL,
    phone_user              VARCHAR(15),
    work_user               VARCHAR(150),
    nb_child_user           INT DEFAULT 0,
    account_valid           TINYINT(1) NOT NULL DEFAULT 0,
    reg_visible             TINYINT(1) NOT NULL DEFAULT 1,
    theme                   VARCHAR(50) DEFAULT 'feminin',
    celibat_geo             TINYINT(1) NOT NULL DEFAULT 0,
    id_corps_armee          INT NOT NULL,
    id_situation            INT NOT NULL,
    CONSTRAINT fk_account_corps_armee
        FOREIGN KEY (id_corps_armee) REFERENCES corps_armee(id_corps_armee),
    CONSTRAINT fk_account_situation
        FOREIGN KEY (id_situation) REFERENCES situation_relationship(id_situation)
);

-- ============================================================
CREATE TABLE publication (
    id_publication              INT AUTO_INCREMENT PRIMARY KEY,
    date_creation_publication   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    contenu_publication         VARCHAR(1500),
    desc_publication            VARCHAR(1500)
);

CREATE TABLE commentaire (
    id_commentaire              INT AUTO_INCREMENT PRIMARY KEY,
    date_creation_commentaire   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    contenu_commentaire         VARCHAR(1500) NOT NULL
);

CREATE TABLE article_ressource (
    id_article              INT AUTO_INCREMENT PRIMARY KEY,
    date_creation_article   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    title_article           VARCHAR(1550) NOT NULL,
    contenu_article         VARCHAR(3000) NOT NULL,
    source_article          VARCHAR(1550)
);

-- ============================================================
CREATE TABLE message_private (
    id_message_private          INT AUTO_INCREMENT PRIMARY KEY,
    account_user_emetteur       INT NOT NULL,
    account_user_destinataire   INT NOT NULL,
    contenu_message              VARCHAR(8000) NOT NULL,
    date_envoi_message           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    message_lu                   TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_message_emetteur
        FOREIGN KEY (account_user_emetteur) REFERENCES account_user(id),
    CONSTRAINT fk_message_destinataire
        FOREIGN KEY (account_user_destinataire) REFERENCES account_user(id)
);

-- ============================================================
-- Tables de jonction n,n
-- ============================================================
CREATE TABLE aimer_publication (
    id_user         INT NOT NULL,
    id_publication  INT NOT NULL,
    PRIMARY KEY (id_user, id_publication),
    FOREIGN KEY (id_user) REFERENCES account_user(id),
    FOREIGN KEY (id_publication) REFERENCES publication(id_publication)
);

CREATE TABLE aimer_commentaire (
    id_user         INT NOT NULL,
    id_commentaire  INT NOT NULL,
    PRIMARY KEY (id_user, id_commentaire),
    FOREIGN KEY (id_user) REFERENCES account_user(id),
    FOREIGN KEY (id_commentaire) REFERENCES commentaire(id_commentaire)
);

CREATE TABLE aimer_article (
    id_user     INT NOT NULL,
    id_article  INT NOT NULL,
    PRIMARY KEY (id_user, id_article),
    FOREIGN KEY (id_user) REFERENCES account_user(id),
    FOREIGN KEY (id_article) REFERENCES article_ressource(id_article)
);

CREATE TABLE rediger_publication (
    id_user         INT NOT NULL,
    id_publication  INT NOT NULL,
    PRIMARY KEY (id_user, id_publication),
    FOREIGN KEY (id_user) REFERENCES account_user(id),
    FOREIGN KEY (id_publication) REFERENCES publication(id_publication)
);

CREATE TABLE rediger_commentaire (
    id_user         INT NOT NULL,
    id_commentaire  INT NOT NULL,
    PRIMARY KEY (id_user, id_commentaire),
    FOREIGN KEY (id_user) REFERENCES account_user(id),
    FOREIGN KEY (id_commentaire) REFERENCES commentaire(id_commentaire)
);

CREATE TABLE rediger_article (
    id_admin    INT NOT NULL,
    id_article  INT NOT NULL,
    PRIMARY KEY (id_admin, id_article),
    FOREIGN KEY (id_admin) REFERENCES administrateur(id_admin),
    FOREIGN KEY (id_article) REFERENCES article_ressource(id_article)
);