<?php

require_once __DIR__ . '/../config/connexion.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (!verifier_jeton_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Requête invalide, merci de recharger la page et de réessayer.');
}

$pdo = obtenir_connexion();

$email_user = trim($_POST['email_user'] ?? '');
$password_user = $_POST['password_user'] ?? '';
$adresse_ip = $_SERVER['REMOTE_ADDR'];

const MAX_TENTATIVES = 5;
const DUREE_BLOCAGE_MINUTES = 15;
const MAX_TENTATIVES_IP = 20;          // Point 6 : seuil plus large que par compte,
const DUREE_BLOCAGE_IP_MINUTES = 15;   // car une IP légitime peut représenter plusieurs personnes (NAT, box partagée...)

// Hash bidon, généré une fois pour toutes. Sert uniquement à faire
// calculer un bcrypt à password_verify() même quand le compte n'existe
// pas, pour que le temps de réponse soit le même dans les deux cas
// (point 4 : anti-énumération par timing).
//
// ⚠️ À REMPLACER : générez le vôtre sur votre serveur avec :
//   php -r 'echo password_hash("valeur_aleatoire_sans_importance", PASSWORD_BCRYPT), PHP_EOL;'
// et collez le résultat ci-dessous (je n'ai pas d'interpréteur PHP
// disponible ici pour le générer moi-même de façon fiable).
const HASH_BIDON = '$2y$10$CwTycUXWue0Thq9StjUM0uJ8i.gU7VRfC3Q5g6Ct7HYmFVOxJm3YO';

// ============================================================
// Point 6 : throttling par IP, en plus du throttling par compte
// déjà en place. Protège contre le "credential stuffing" (un
// attaquant qui teste un mot de passe sur des milliers de comptes
// depuis une seule IP, sans jamais atteindre le seuil par compte).
// Nécessite la table tentatives_connexion_ip (voir migration SQL).
// ============================================================
$stmt_ip = $pdo->prepare('SELECT tentatives, bloque_jusqu_a FROM tentatives_connexion_ip WHERE ip = ?');
$stmt_ip->execute([$adresse_ip]);
$suivi_ip = $stmt_ip->fetch();

if ($suivi_ip && $suivi_ip['bloque_jusqu_a'] !== null && new DateTime($suivi_ip['bloque_jusqu_a']) > new DateTime()) {
    header('Location: /connexion?bloque=1');
    exit;
}

$stmt = $pdo->prepare('
    SELECT id, password_user, tentatives_echouees, bloque_jusqu_a
    FROM account_user
    WHERE email_user = ?
');
$stmt->execute([$email_user]);
$compte = $stmt->fetch();

// Point 4 : que le compte existe ou non, on effectue TOUJOURS un
// password_verify() coûteux (bcrypt), pour que le temps de réponse ne
// révèle pas si l'email est inscrit ou non. Sans ça, le cas "compte
// inexistant" répond quasi instantanément alors que le cas "mauvais
// mot de passe" prend ~100ms à cause du calcul bcrypt — une différence
// mesurable à distance qui permet de tester des emails en masse.
$mot_de_passe_correct = password_verify(
    $password_user,
    $compte['password_user'] ?? HASH_BIDON
);

if (!$compte) {
    enregistrer_echec_ip($pdo, $adresse_ip, $suivi_ip);
    header('Location: /connexion?erreur=1');
    exit;
}

// Vérification du blocage temporaire du compte (anti brute-force ciblé)
if ($compte['bloque_jusqu_a'] !== null && new DateTime($compte['bloque_jusqu_a']) > new DateTime()) {
    header('Location: /connexion?bloque=1');
    exit;
}

if (!$mot_de_passe_correct) {
    enregistrer_echec_ip($pdo, $adresse_ip, $suivi_ip);

    $nouvelles_tentatives = $compte['tentatives_echouees'] + 1;

    if ($nouvelles_tentatives >= MAX_TENTATIVES) {
        $stmt_blocage = $pdo->prepare('
            UPDATE account_user
            SET tentatives_echouees = 0,
                bloque_jusqu_a = DATE_ADD(NOW(), INTERVAL ' . DUREE_BLOCAGE_MINUTES . ' MINUTE)
            WHERE id = ?
        ');
        $stmt_blocage->execute([$compte['id']]);
        header('Location: /connexion?bloque=1');
        exit;
    }

    $stmt_echec = $pdo->prepare('UPDATE account_user SET tentatives_echouees = ? WHERE id = ?');
    $stmt_echec->execute([$nouvelles_tentatives, $compte['id']]);

    header('Location: /connexion?erreur=1');
    exit;
}

// Connexion réussie : réinitialiser les compteurs (compte + IP)
$stmt_reset = $pdo->prepare('UPDATE account_user SET tentatives_echouees = 0, bloque_jusqu_a = NULL WHERE id = ?');
$stmt_reset->execute([$compte['id']]);

$stmt_reset_ip = $pdo->prepare('DELETE FROM tentatives_connexion_ip WHERE ip = ?');
$stmt_reset_ip->execute([$adresse_ip]);

// Régénérer l'identifiant de session après une connexion réussie :
// empêche une attaque de "fixation de session"
session_regenerate_id(true);

$_SESSION['user_id'] = $compte['id'];

header('Location: /page_feed.php');
exit;

/**
 * Incrémente le compteur d'échecs pour une IP, et la bloque
 * temporairement si le seuil est dépassé.
 */
function enregistrer_echec_ip(PDO $pdo, string $ip, $suivi_actuel): void
{
    $nouvelles_tentatives = ($suivi_actuel['tentatives'] ?? 0) + 1;

    if ($nouvelles_tentatives >= MAX_TENTATIVES_IP) {
        $stmt = $pdo->prepare('
            INSERT INTO tentatives_connexion_ip (ip, tentatives, bloque_jusqu_a)
            VALUES (:ip, 0, DATE_ADD(NOW(), INTERVAL ' . DUREE_BLOCAGE_IP_MINUTES . ' MINUTE))
            ON DUPLICATE KEY UPDATE
                tentatives = 0,
                bloque_jusqu_a = DATE_ADD(NOW(), INTERVAL ' . DUREE_BLOCAGE_IP_MINUTES . ' MINUTE)
        ');
        $stmt->execute(['ip' => $ip]);
        return;
    }

    $stmt = $pdo->prepare('
        INSERT INTO tentatives_connexion_ip (ip, tentatives, bloque_jusqu_a)
        VALUES (:ip, 1, NULL)
        ON DUPLICATE KEY UPDATE tentatives = :tentatives
    ');
    $stmt->execute(['ip' => $ip, 'tentatives' => $nouvelles_tentatives]);
}