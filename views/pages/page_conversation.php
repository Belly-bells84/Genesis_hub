<?php
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../models/class_message.php';
require_once __DIR__ . '/../../exceptions/Page_introuvable.php';

$pdo = obtenir_connexion();
$messageRepo = new MessagePrivate($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];

// $id_contact_message est défini dans index.php via preg_match sur
// l'URL /messages/{id}, avant l'inclusion de ce fichier.
if (!$messageRepo->utilisateurExiste($id_contact_message)) {
    throw new PageIntrouvableException('messages/' . $id_contact_message);
}

$contact = $messageRepo->recupUtilisateur($id_contact_message);
$messages = $messageRepo->recupMessages($id_utilisateur, $id_contact_message);
$messageRepo->marquerCommeLu($id_utilisateur, $id_contact_message);

$dernier_id = !empty($messages) ? (int) end($messages)['id_message_private'] : 0;
?>


<script src="/views/js/message.js" defer></script>
<link rel="stylesheet" href="/views/css/conversation_style.css">

<div class="messages-layout">
    <?php require __DIR__ . '/partials/message_sidebar.php'; ?>

    <div
        class="conversation-page"
        data-id-contact="<?= (int) $id_contact_message ?>"
        data-id-utilisateur="<?= $id_utilisateur ?>"
        data-dernier-id="<?= $dernier_id ?>"
    >
        <h1><?= htmlspecialchars($contact['account_name']) ?></h1>

        <div class="fil-messages" id="fil-messages">
            <?php foreach ($messages as $message): ?>
                <p
                    class="message <?= (int) $message['account_user_emetteur'] === $id_utilisateur ? 'message-envoye' : 'message-recu' ?>"
                    data-id-message="<?= (int) $message['id_message_private'] ?>"
                >
                    <?= nl2br(htmlspecialchars($message['contenu_message'])) ?>
                    <br>
                    <span class="message-date"><?= (new DateTime($message['date_envoi_message']))->format('d/m/Y H:i') ?></span>
                </p>
            <?php endforeach; ?>
        </div>

        <form id="form-envoi-message" class="form-envoi-message">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">
            <input type="hidden" name="id_destinataire" value="<?= (int) $id_contact_message ?>">
            <input type="text" name="contenu_message" maxlength="8000" required placeholder="Écrire un message...">
            <button type="submit">Envoyer</button>
        </form>
    </div>
</div>