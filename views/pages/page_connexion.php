<?php
require_once __DIR__ . '/../../config/csrf.php';
?>
<link rel="stylesheet" href="/views/css/connexion_style.css">
<form id="form-connexion" class="wizard-inscription" action="/connexion/traiter" method="POST">

    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">

    <fieldset class="etape">
        <legend>Se connecter</legend>

        <?php if (isset($_GET['erreur'])): ?>
            <p class="message-erreur">Email ou mot de passe incorrect.</p>
        <?php endif; ?>

        <?php if (isset($_GET['bloque'])): ?>
            <p class="message-erreur">Trop de tentatives échouées. Réessaie dans quelques minutes.</p>
        <?php endif; ?>

        <?php if (isset($_GET['inscription']) && $_GET['inscription'] === 'succes'): ?>
            <p class="message-succes">Compte créé avec succès, tu peux maintenant te connecter.</p>
        <?php endif; ?>

        <label for="email_user">Email</label>
        <input type="email" id="email_user" name="email_user" required maxlength="255">

        <label for="password_user">Mot de passe</label>
        <input type="password" id="password_user" name="password_user" required>

        <button type="submit" class="bouton-valider">Connexion</button>
    </fieldset>

</form>