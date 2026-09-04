<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// session_start() doit être appelé avant tout affichage HTML — c'est ce qui
// permet à $_SESSION d'exister (nécessaire pour le jeton CSRF, et plus tard
// pour savoir si une utilisatrice est connectée)
session_start();

require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/exceptions/App_exception.php';
require_once __DIR__ . '/exceptions/Page_introuvable.php';
require_once __DIR__ . '/exceptions/Error_affichage_exception.php';


$page = $_GET['url'] ?? '';

/**
 * Inclut une vue en vérifiant d'abord son existence, pour transformer un
 * fichier manquant en ErreurAffichageException plutôt qu'un Warning PHP
 * brut qui laisserait la page à moitié rendue.
 */
function inclure_vue(string $page, string $chemin_vue): void
{
    // Rend visibles aux vues incluses les variables définies dans la portée
    // globale de index.php. Sans ce mot-clé, include() ici s'exécute dans
    // la portée LOCALE de cette fonction, qui ne voit pas automatiquement
    // les variables globales comme $id_profil_consulte ou $id_contact_message.
    global $id_profil_consulte, $id_contact_message;

    if (!is_file($chemin_vue)) {
        throw new ErreurAffichageException($page, $chemin_vue);
    }

    include $chemin_vue;
}

try {
    // ============================================================
    // Routes de traitement : ne produisent jamais de HTML, seulement
    // une redirection ou un message d'erreur brut. Elles doivent être
    // interceptées AVANT d'inclure header.php, car header('Location: ...')
    // échoue si du HTML a déjà été envoyé au navigateur.
    // ============================================================
    if ($page === 'inscription/traiter') {
        include __DIR__ . '/controller/traiter_inscription.php';
        exit;
    }

    if ($page === 'connexion/traiter') {
        include __DIR__ . '/controller/traiter_connexion.php';
        exit;
    }

    if ($page === 'profil/traiter') {
        include __DIR__ . '/controller/traiter_profil.php';
        exit;
    }

    if ($page === 'profil/mot-de-passe/traiter') {
        include __DIR__ . '/controller/traiter_mdp.php';
        exit;
    }

    // Routes de traitement du feed : mêmes règles que les autres routes
    // /*/traiter ci-dessus — chaque contrôleur vérifie lui-même la
    // connexion (401 si absente) et le jeton CSRF, donc rien de plus à
    // faire ici avant de les inclure.
    if ($page === 'feed/publier') {
        include __DIR__ . '/controller/traiter_publication.php';
        exit;
    }

    if ($page === 'feed/aimer') {
        include __DIR__ . '/controller/traiter_like.php';
        exit;
    }

    if ($page === 'feed/commenter') {
        include __DIR__ . '/controller/traiter_commentaire.php';
        exit;
    }
    
    if ($page === 'feed/supprimer') {
        include __DIR__ . '/controller/traiter_suppression_publication.php';
        exit;
    }

    //Routes traitement des messages : 

    if ($page === 'messages/envoyer') {
    include __DIR__ . '/controller/traiter_send_message.php';
    exit;
    }

    if ($page === 'messages/nouveaux') {
        include __DIR__ . '/controller/traiter_new_message.php';
        exit;
    }

    if ($page === 'messages/rechercher') {
        include __DIR__ . '/controller/traiter_message_search.php';
        exit;
    }
    if ($page === 'deconnexion') {
            $_SESSION = [];
            session_destroy();
            header('Location: /connexion');
            exit;
        }
    // Contrôle d'accès en liste blanche : toute page absente de cette liste
    // exige une connexion. Une nouvelle page ajoutée = protégée par défaut.
    // "feed" n'y figure pas volontairement : consulter/publier sur le feed
    // nécessite d'être connectée, comme "profil".
    $pages_publiques = ['', 'inscription', 'connexion'];

    if (!in_array($page, $pages_publiques, true)) {
        // exiger_connexion() redirige elle-même vers /connexion et fait
        // exit si l'utilisatrice n'est pas authentifiée — elle ne lève
        // pas d'exception, donc rien à attraper ici.
        exiger_connexion();
    }

    // /profil          => son propre profil (édition)
    // /profil/{id}     => le profil d'une autre utilisatrice (lecture seule)
    // les deux routes sont normalisées vers le même $page='profil' pour
    // que le switch les traite ensemble ; $id_profil_consulte distingue
    // les deux cas dans page_profil.php.
    $id_profil_consulte = null;

    if ($page === 'profil') {
        $id_profil_consulte = (int) $_SESSION['user_id'];
    } elseif (preg_match('#^profil/(\d+)$#', $page, $matches)) {
        $id_profil_consulte = (int) $matches[1];
        $page = 'profil';
    }

    $id_contact_message = null;

    if (preg_match('#^messages/(\d+)$#', $page, $matches)) {
        $id_contact_message = (int) $matches[1];
        $page = 'messages/conversation';
    }
//Pourquoi le tableau ? Pour la maintenanbilité, la factorisation et la lisibilité du code.
    $routes = [
        ''     => __DIR__ . '/views/pages/welcome.php',
        'inscription' => __DIR__ . '/views/pages/page_inscription.php',
        'connexion'   => __DIR__ . '/views/pages/page_connexion.php',
        'profil'      => __DIR__ . '/views/pages/page_profil.php',
        'feed'        => __DIR__ . '/views/pages/page_feed.php',
        'messages'    => __DIR__ . '/views/pages/page_message.php',
        'messages/conversation'=> __DIR__ . '/views/pages/page_conversation.php',
    ];

    if (!isset($routes[$page])) {
        throw new PageIntrouvableException($page);
    }

    inclure_vue($page, __DIR__ . '/views/includes/header.php');
    inclure_vue($page, $routes[$page]);
    inclure_vue($page, __DIR__ . '/views/includes/footer.php');

} catch (PageIntrouvableException $e) {
    error_log('[404] ' . $e->getMessage());
    http_response_code($e->getCodeHttp());
    echo '<p>Page introuvable.</p>';

} catch (AppException $e) {
    // Toute autre exception applicative connue (ErreurAffichageException,
    // ou une future exception métier) : on affiche son code HTTP et un
    // message générique, sans exposer le détail technique à l'écran.
    error_log('[Erreur applicative] ' . $e->getMessage());
    http_response_code($e->getCodeHttp());
    echo '<p>Une erreur est survenue lors du traitement de votre demande.</p>';

} catch (Throwable $e) {
    error_log('[Erreur inattendue] ' . $e->getMessage());
    http_response_code(500);
    echo '<p>Une erreur inattendue est survenue. Merci de réessayer plus tard.</p>';
}