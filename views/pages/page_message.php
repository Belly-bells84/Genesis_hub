<?php
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../models/class_message.php';

$pdo = obtenir_connexion();
$messageRepo = new MessagePrivate($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];
?>

<script src="/views/js/message.js" defer></script>
<link rel="stylesheet" href="/views/css/message_style.css">

<div class="messages-layout">
    <?php require __DIR__ . '/partials/message_sidebar.php'; ?>

    <div class="messages-panel-vide">
        <p>Sélectionnez une conversation à gauche, ou recherchez une utilisatrice pour commencer à discuter.</p>
    </div>
</div>