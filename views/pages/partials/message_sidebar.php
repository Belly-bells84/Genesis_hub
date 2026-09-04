<?php
// Ce fichier attend que $messageRepo et $id_utilisateur soient déjà
// définis par la vue qui l'inclut (page_message.php ou page_conversation.php).
// $id_contact_message, s'il existe, sert à surligner la conversation
// actuellement ouverte dans la liste.
$conversations = $messageRepo->recupConversations($id_utilisateur);
?>
<aside class="messages-sidebar">
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
            <p>Aucune conversation pour l'instant.</p>
        <?php endif; ?>

        <?php foreach ($conversations as $conversation): ?>
            <?php
                $est_active = isset($id_contact_message)
                    && (int) $conversation['id_contact'] === (int) $id_contact_message;
            ?>
            <li class="conversation-apercu
                <?= $conversation['nb_non_lus'] > 0 ? 'non-lu' : '' ?>
                <?= $est_active ? 'conversation-active' : '' ?>">
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
</aside>