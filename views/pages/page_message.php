<?php
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../models/class_message.php';

$pdo = obtenir_connexion();
$messageRepo = new MessagePrivate($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];
$conversations = $messageRepo->recupConversations($id_utilisateur);
?>
<script src="/views/js/messages.js" defer></script></div>
<div class="messages-page">
    <h1>Messages</h1>

    <div class="messages-recherche">
        <input
            type="text"
            id="recherche-utilisateur"
            placeholder="Rechercher une utilisatrice pour lui écrire..."
            autocomplete="off"
        >
        <ul id="resultats-recherche" class="resultats-recherche"></ul>
    </div>

    <ul class="liste-conversations">
        <?php if (empty($conversations)): ?>
            <p>Aucune conversation pour l'instant. Recherchez une utilisatrice ci-dessus pour commencer à discuter.</p>
        <?php endif; ?>

        <?php foreach ($conversations as $conversation): ?>
            <li class="conversation-apercu <?= $conversation['nb_non_lus'] > 0 ? 'non-lu' : '' ?>">
                <a href="/messages/<?= (int) $conversation['id_contact'] ?>">
                    <span class="conversation-nom"><?= htmlspecialchars($conversation['account_name']) ?></span>
                    <span class="conversation-dernier-message">
                        <?= htmlspecialchars(mb_strimwidth($conversation['dernier_message'], 0, 60, '...')) ?>
                    </span>
                    <?php if ($conversation['nb_non_lus'] > 0): ?>
                        <span class="badge-non-lu"><?= (int) $conversation['nb_non_lus'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>