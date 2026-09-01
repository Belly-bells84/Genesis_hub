-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 01 sep. 2026 à 09:16
-- Version du serveur : 8.4.7
-- Version de PHP : 8.5.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `genesis_dev`
--

-- --------------------------------------------------------

--
-- Structure de la table `account_user`
--

DROP TABLE IF EXISTS `account_user`;
CREATE TABLE IF NOT EXISTS `account_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pictures_user` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `desc_name` varchar(1500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reg_partenaire_count` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_birth_user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_user` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nb_child_user` int DEFAULT '0',
  `account_valid` tinyint(1) NOT NULL DEFAULT '0',
  `reg_visible` tinyint(1) NOT NULL DEFAULT '1',
  `theme` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'feminin',
  `celibat_geo` tinyint(1) NOT NULL DEFAULT '0',
  `id_corps_armee` int NOT NULL,
  `id_situation` int NOT NULL,
  `id_sous_corps_armee` int DEFAULT NULL,
  `est_majeur` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Calculé et stocké en clair à l inscription, car date_birth_user est chiffrée',
  `tentatives_echouees` int NOT NULL DEFAULT '0',
  `bloque_jusqu_a` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_user` (`email_user`),
  UNIQUE KEY `uniq_email_user` (`email_user`),
  KEY `fk_account_corps_armee` (`id_corps_armee`),
  KEY `fk_account_situation` (`id_situation`),
  KEY `fk_account_sous_corps` (`id_sous_corps_armee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `administrateur`
--

DROP TABLE IF EXISTS `administrateur`;
CREATE TABLE IF NOT EXISTS `administrateur` (
  `id_admin` int NOT NULL AUTO_INCREMENT,
  `work_admin` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `aimer_article`
--

DROP TABLE IF EXISTS `aimer_article`;
CREATE TABLE IF NOT EXISTS `aimer_article` (
  `id_user` int NOT NULL,
  `id_article` int NOT NULL,
  PRIMARY KEY (`id_user`,`id_article`),
  KEY `id_article` (`id_article`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `aimer_commentaire`
--

DROP TABLE IF EXISTS `aimer_commentaire`;
CREATE TABLE IF NOT EXISTS `aimer_commentaire` (
  `id_user` int NOT NULL,
  `id_commentaire` int NOT NULL,
  PRIMARY KEY (`id_user`,`id_commentaire`),
  KEY `id_commentaire` (`id_commentaire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `aimer_publication`
--

DROP TABLE IF EXISTS `aimer_publication`;
CREATE TABLE IF NOT EXISTS `aimer_publication` (
  `id_user` int NOT NULL,
  `id_publication` int NOT NULL,
  PRIMARY KEY (`id_user`,`id_publication`),
  KEY `id_publication` (`id_publication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `article_ressource`
--

DROP TABLE IF EXISTS `article_ressource`;
CREATE TABLE IF NOT EXISTS `article_ressource` (
  `id_article` int NOT NULL AUTO_INCREMENT,
  `date_creation_article` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title_article` varchar(1550) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu_article` varchar(3000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_article` varchar(1550) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_article`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

DROP TABLE IF EXISTS `commentaire`;
CREATE TABLE IF NOT EXISTS `commentaire` (
  `id_commentaire` int NOT NULL AUTO_INCREMENT,
  `date_creation_commentaire` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `contenu_commentaire` varchar(1500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_publication` int NOT NULL,
  PRIMARY KEY (`id_commentaire`),
  KEY `fk_commentaire_publication` (`id_publication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `corps_armee`
--

DROP TABLE IF EXISTS `corps_armee`;
CREATE TABLE IF NOT EXISTS `corps_armee` (
  `id_corps_armee` int NOT NULL AUTO_INCREMENT,
  `libelle_corps_armee` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sous_corps_obligatoire` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_corps_armee`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `corps_armee`
--

INSERT INTO `corps_armee` (`id_corps_armee`, `libelle_corps_armee`, `sous_corps_obligatoire`) VALUES
(1, 'Armée de Terre', 1),
(2, 'Marine Nationale', 0),
(3, 'Armée de l\'Air et de l\'Espace', 0),
(4, 'Gendarmerie Nationale', 1);

-- --------------------------------------------------------

--
-- Structure de la table `message_private`
--

DROP TABLE IF EXISTS `message_private`;
CREATE TABLE IF NOT EXISTS `message_private` (
  `id_message_private` int NOT NULL AUTO_INCREMENT,
  `account_user_emetteur` int NOT NULL,
  `account_user_destinataire` int NOT NULL,
  `contenu_message` varchar(8000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_envoi_message` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `message_lu` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_message_private`),
  KEY `fk_message_emetteur` (`account_user_emetteur`),
  KEY `fk_message_destinataire` (`account_user_destinataire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `publication`
--

DROP TABLE IF EXISTS `publication`;
CREATE TABLE IF NOT EXISTS `publication` (
  `id_publication` int NOT NULL AUTO_INCREMENT,
  `date_creation_publication` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `contenu_publication` varchar(1500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desc_publication` varchar(1500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_publication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rediger_article`
--

DROP TABLE IF EXISTS `rediger_article`;
CREATE TABLE IF NOT EXISTS `rediger_article` (
  `id_admin` int NOT NULL,
  `id_article` int NOT NULL,
  PRIMARY KEY (`id_admin`,`id_article`),
  KEY `id_article` (`id_article`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rediger_commentaire`
--

DROP TABLE IF EXISTS `rediger_commentaire`;
CREATE TABLE IF NOT EXISTS `rediger_commentaire` (
  `id_user` int NOT NULL,
  `id_commentaire` int NOT NULL,
  PRIMARY KEY (`id_user`,`id_commentaire`),
  KEY `id_commentaire` (`id_commentaire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rediger_publication`
--

DROP TABLE IF EXISTS `rediger_publication`;
CREATE TABLE IF NOT EXISTS `rediger_publication` (
  `id_user` int NOT NULL,
  `id_publication` int NOT NULL,
  PRIMARY KEY (`id_user`,`id_publication`),
  KEY `id_publication` (`id_publication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `situation_relationship`
--

DROP TABLE IF EXISTS `situation_relationship`;
CREATE TABLE IF NOT EXISTS `situation_relationship` (
  `id_situation` int NOT NULL AUTO_INCREMENT,
  `libelle_situation` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_situation`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `situation_relationship`
--

INSERT INTO `situation_relationship` (`id_situation`, `libelle_situation`) VALUES
(1, 'Concubinage'),
(2, 'PACS'),
(3, 'Marié(e)');

-- --------------------------------------------------------

--
-- Structure de la table `sous_corps_armee`
--

DROP TABLE IF EXISTS `sous_corps_armee`;
CREATE TABLE IF NOT EXISTS `sous_corps_armee` (
  `id_sous_corps_armee` int NOT NULL AUTO_INCREMENT,
  `libelle_sous_corps` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_corps_armee` int NOT NULL,
  PRIMARY KEY (`id_sous_corps_armee`),
  KEY `fk_sous_corps_corps_armee` (`id_corps_armee`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sous_corps_armee`
--

INSERT INTO `sous_corps_armee` (`id_sous_corps_armee`, `libelle_sous_corps`, `id_corps_armee`) VALUES
(1, 'Légion Étrangère', 1),
(2, 'Pompier militaire', 1),
(3, 'Pompier militaire', 2),
(4, 'Pompier militaire', 3),
(5, 'Mobile', 4),
(6, 'Départementale', 4);

-- --------------------------------------------------------

--
-- Structure de la table `tentatives_connexion_ip`
--

DROP TABLE IF EXISTS `tentatives_connexion_ip`;
CREATE TABLE IF NOT EXISTS `tentatives_connexion_ip` (
  `ip` varchar(45) NOT NULL,
  `tentatives` int UNSIGNED NOT NULL DEFAULT '0',
  `bloque_jusqu_a` datetime DEFAULT NULL,
  `derniere_tentative` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `account_user`
--
ALTER TABLE `account_user`
  ADD CONSTRAINT `fk_account_corps_armee` FOREIGN KEY (`id_corps_armee`) REFERENCES `corps_armee` (`id_corps_armee`),
  ADD CONSTRAINT `fk_account_situation` FOREIGN KEY (`id_situation`) REFERENCES `situation_relationship` (`id_situation`),
  ADD CONSTRAINT `fk_account_sous_corps` FOREIGN KEY (`id_sous_corps_armee`) REFERENCES `sous_corps_armee` (`id_sous_corps_armee`);

--
-- Contraintes pour la table `aimer_article`
--
ALTER TABLE `aimer_article`
  ADD CONSTRAINT `aimer_article_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `account_user` (`id`),
  ADD CONSTRAINT `aimer_article_ibfk_2` FOREIGN KEY (`id_article`) REFERENCES `article_ressource` (`id_article`);

--
-- Contraintes pour la table `aimer_commentaire`
--
ALTER TABLE `aimer_commentaire`
  ADD CONSTRAINT `aimer_commentaire_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `account_user` (`id`),
  ADD CONSTRAINT `aimer_commentaire_ibfk_2` FOREIGN KEY (`id_commentaire`) REFERENCES `commentaire` (`id_commentaire`);

--
-- Contraintes pour la table `aimer_publication`
--
ALTER TABLE `aimer_publication`
  ADD CONSTRAINT `aimer_publication_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `account_user` (`id`),
  ADD CONSTRAINT `aimer_publication_ibfk_2` FOREIGN KEY (`id_publication`) REFERENCES `publication` (`id_publication`);

--
-- Contraintes pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD CONSTRAINT `fk_commentaire_publication` FOREIGN KEY (`id_publication`) REFERENCES `publication` (`id_publication`);

--
-- Contraintes pour la table `message_private`
--
ALTER TABLE `message_private`
  ADD CONSTRAINT `fk_message_destinataire` FOREIGN KEY (`account_user_destinataire`) REFERENCES `account_user` (`id`),
  ADD CONSTRAINT `fk_message_emetteur` FOREIGN KEY (`account_user_emetteur`) REFERENCES `account_user` (`id`);

--
-- Contraintes pour la table `rediger_article`
--
ALTER TABLE `rediger_article`
  ADD CONSTRAINT `rediger_article_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `administrateur` (`id_admin`),
  ADD CONSTRAINT `rediger_article_ibfk_2` FOREIGN KEY (`id_article`) REFERENCES `article_ressource` (`id_article`);

--
-- Contraintes pour la table `rediger_commentaire`
--
ALTER TABLE `rediger_commentaire`
  ADD CONSTRAINT `rediger_commentaire_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `account_user` (`id`),
  ADD CONSTRAINT `rediger_commentaire_ibfk_2` FOREIGN KEY (`id_commentaire`) REFERENCES `commentaire` (`id_commentaire`);

--
-- Contraintes pour la table `rediger_publication`
--
ALTER TABLE `rediger_publication`
  ADD CONSTRAINT `rediger_publication_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `account_user` (`id`),
  ADD CONSTRAINT `rediger_publication_ibfk_2` FOREIGN KEY (`id_publication`) REFERENCES `publication` (`id_publication`);

--
-- Contraintes pour la table `sous_corps_armee`
--
ALTER TABLE `sous_corps_armee`
  ADD CONSTRAINT `fk_sous_corps_corps_armee` FOREIGN KEY (`id_corps_armee`) REFERENCES `corps_armee` (`id_corps_armee`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
