<?php
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../config/csrf.php';
$pdo = obtenir_connexion();

$corps_armee_liste = $pdo->query('SELECT id_corps_armee, libelle_corps_armee FROM corps_armee')->fetchAll();
$sous_corps_liste  = $pdo->query('SELECT id_sous_corps_armee, libelle_sous_corps, id_corps_armee FROM sous_corps_armee')->fetchAll();
$situation_liste   = $pdo->query('SELECT id_situation, libelle_situation FROM situation_relationship')->fetchAll();
?>

<form id="form-inscription" class="wizard-inscription" action="/inscription/traiter" method="POST" enctype="multipart/form-data">

    <!-- Jeton CSRF : invisible pour l'utilisatrice => nouvelle vérification dans le fichier : traiter_inscription.php -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">

    <!-- ÉTAPE 1 : identifiants-->
    <fieldset class="etape" data-step="1">
        <legend>Créer mon compte</legend>

        <label for="account_name">Pseudo</label>
        <input type="text" id="account_name" name="account_name" required maxlength="150">

        <label for="email_user">Email</label>
        <input type="email" id="email_user" name="email_user" required maxlength="255">

        <label for="password_user">Mot de passe</label>
        <input type="password" id="password_user" name="password_user" required minlength="8">

        <label for="password_confirmation">Confirmer le mot de passe</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">

        <label for="date_birth_user">Date de naissance</label>
        <input type="date" id="date_birth_user" name="date_birth_user" required>

        <button type="button" class="fleche-suivant" aria-label="Étape suivante">→</button>
    </fieldset>

    <!-- ÉTAPE 2 : corps d'armée + situation-->
    <fieldset class="etape" data-step="2" hidden>
        <legend>Quel est son corps d'armée ?</legend>

        <div class="toggle-groupe" id="groupe-corps-armee">
            <?php foreach ($corps_armee_liste as $corps): ?>
                <label class="toggle">
                    <input
                        type="radio"
                        name="id_corps_armee"
                        value="<?= htmlspecialchars($corps['id_corps_armee']) ?>"
                        required
                    >
                    <?= htmlspecialchars($corps['libelle_corps_armee']) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <!-- Sous-corps : un bloc par corps_armee, affiché uniquement si sélectionné (JS) -->
        <?php foreach ($corps_armee_liste as $corps): ?>
            <div class="toggle-groupe sous-corps" data-parent-corps="<?= htmlspecialchars($corps['id_corps_armee']) ?>" hidden>
                <?php foreach ($sous_corps_liste as $sous): ?>
                    <?php if ($sous['id_corps_armee'] == $corps['id_corps_armee']): ?>
                        <label class="toggle">
                            <input
                                type="radio"
                                name="id_sous_corps_armee"
                                value="<?= htmlspecialchars($sous['id_sous_corps_armee']) ?>"
                            >
                            <?= htmlspecialchars($sous['libelle_sous_corps']) ?>
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <legend>Quelle est votre situation ?</legend>

        <div class="toggle-groupe" id="groupe-situation">
            <?php foreach ($situation_liste as $situation): ?>
                <label class="toggle">
                    <input
                        type="radio"
                        name="id_situation"
                        value="<?= htmlspecialchars($situation['id_situation']) ?>"
                        required
                    >
                    <?= htmlspecialchars($situation['libelle_situation']) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="toggle-groupe">
            <label class="toggle">
                <input type="radio" name="celibat_geo" value="1" required>
                Célibat géographique
            </label>
            <label class="toggle">
                <input type="radio" name="celibat_geo" value="0" required>
                Vie commune
            </label>
        </div>

        <button type="button" class="fleche-retour" aria-label="Étape précédente">←</button>
        <button type="button" class="fleche-suivant" aria-label="Étape suivante">→</button>
    </fieldset>

    
    <!-- ÉTAPE 3 : photo de profil-->
    <fieldset class="etape" data-step="3" hidden>
        <legend>Ajoute une photo de profil</legend>

        <label for="pictures_user">Photo (facultatif)</label>
        <input type="file" id="pictures_user" name="pictures_user" accept="image/png, image/jpeg, image/webp">

        <button type="button" class="fleche-retour" aria-label="Étape précédente">←</button>
        <button type="submit" class="bouton-valider">Créer mon compte</button>
    </fieldset>

</form>

<script src="/asset/JS/inscription.js"></script>