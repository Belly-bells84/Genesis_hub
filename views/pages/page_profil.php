<?php
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../config/chiffrement.php';
require_once __DIR__ . '/../../config/csrf.php';
$pdo = obtenir_connexion();

$est_propre_profil = ($id_profil_consulte === (int) $_SESSION['user_id']);

$stmt = $pdo->prepare('
    SELECT au.*, ca.libelle_corps_armee, sca.libelle_sous_corps, sr.libelle_situation
    FROM account_user au
    JOIN corps_armee ca ON ca.id_corps_armee = au.id_corps_armee
    LEFT JOIN sous_corps_armee sca ON sca.id_sous_corps_armee = au.id_sous_corps_armee
    JOIN situation_relationship sr ON sr.id_situation = au.id_situation
    WHERE au.id = ?
');
$stmt->execute([$id_profil_consulte]);
$profil = $stmt->fetch();

// Profil inexistant, ou désactivé par sa propriétaire et consulté par une autre personne
if (!$profil || (!$est_propre_profil && (int) $profil['reg_visible'] === 0)) {
    http_response_code(404);
    echo '<p>Profil introuvable.</p>';
    return;
}

// Le déchiffrement des données sensibles ne se fait JAMAIS pour un profil
// consulté par quelqu'un d'autre — seule la propriétaire peut les voir.
if ($est_propre_profil) {
    $phone_en_clair = $profil['phone_user'] !== null ? dechiffrer($profil['phone_user']) : '';
    $city_en_clair = $profil['city_user'] !== null ? dechiffrer($profil['city_user']) : '';

    $corps_armee_liste = $pdo->query('SELECT id_corps_armee, libelle_corps_armee FROM corps_armee')->fetchAll();
    $sous_corps_liste  = $pdo->query('SELECT id_sous_corps_armee, libelle_sous_corps, id_corps_armee FROM sous_corps_armee')->fetchAll();
    $situation_liste   = $pdo->query('SELECT id_situation, libelle_situation FROM situation_relationship')->fetchAll();
}
?>

<?php if ($est_propre_profil): ?>

    <!-- ============================================================ -->
    <!-- MODE ÉDITION : profil de la propriétaire elle-même            -->
    <!-- ============================================================ -->
    <form class="wizard-inscription" action="/profil/traiter" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">

        <fieldset class="etape">
            <legend>Mon profil</legend>

            <?php if (isset($_GET['succes'])): ?>
                <p class="message-succes">Profil mis à jour.</p>
            <?php endif; ?>

            <label for="account_name">Pseudo</label>
            <input type="text" id="account_name" name="account_name" required maxlength="150"
                   value="<?= htmlspecialchars($profil['account_name']) ?>">

            <label for="desc_name">Bio</label>
            <input type="text" id="desc_name" name="desc_name" maxlength="1500"
                   value="<?= htmlspecialchars($profil['desc_name'] ?? '') ?>">

            <label for="work_user">Métier</label>
            <input type="text" id="work_user" name="work_user" maxlength="150"
                   value="<?= htmlspecialchars($profil['work_user'] ?? '') ?>">

            <label for="phone_user">Téléphone</label>
            <input type="tel" id="phone_user" name="phone_user" maxlength="20"
                   value="<?= htmlspecialchars($phone_en_clair) ?>">

            <label for="city_user">Ville</label>
            <input type="text" id="city_user" name="city_user" maxlength="150"
                   value="<?= htmlspecialchars($city_en_clair) ?>">

            <label for="pictures_user">Nouvelle photo de profil</label>
            <input type="file" id="pictures_user" name="pictures_user" accept="image/png, image/jpeg, image/webp">
        </fieldset>

        <fieldset class="etape">
            <legend>Corps d'armée</legend>

            <div class="toggle-groupe">
                <?php foreach ($corps_armee_liste as $corps): ?>
                    <label class="toggle">
                        <input type="radio" name="id_corps_armee"
                               value="<?= htmlspecialchars($corps['id_corps_armee']) ?>"
                               <?= (int) $corps['id_corps_armee'] === (int) $profil['id_corps_armee'] ? 'checked' : '' ?>
                               required>
                        <?= htmlspecialchars($corps['libelle_corps_armee']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php foreach ($corps_armee_liste as $corps): ?>
                <div class="toggle-groupe sous-corps"
                     data-parent-corps="<?= htmlspecialchars($corps['id_corps_armee']) ?>"
                     <?= (int) $corps['id_corps_armee'] !== (int) $profil['id_corps_armee'] ? 'hidden' : '' ?>>
                    <?php foreach ($sous_corps_liste as $sous): ?>
                        <?php if ($sous['id_corps_armee'] == $corps['id_corps_armee']): ?>
                            <label class="toggle">
                                <input type="radio" name="id_sous_corps_armee"
                                       value="<?= htmlspecialchars($sous['id_sous_corps_armee']) ?>"
                                       <?= (int) $sous['id_sous_corps_armee'] === (int) ($profil['id_sous_corps_armee'] ?? 0) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($sous['libelle_sous_corps']) ?>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <legend>Situation</legend>

            <div class="toggle-groupe">
                <?php foreach ($situation_liste as $situation): ?>
                    <label class="toggle">
                        <input type="radio" name="id_situation"
                               value="<?= htmlspecialchars($situation['id_situation']) ?>"
                               <?= (int) $situation['id_situation'] === (int) $profil['id_situation'] ? 'checked' : '' ?>
                               required>
                        <?= htmlspecialchars($situation['libelle_situation']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="toggle-groupe">
                <label class="toggle">
                    <input type="radio" name="celibat_geo" value="1"
                           <?= (int) $profil['celibat_geo'] === 1 ? 'checked' : '' ?> required>
                    Célibat géographique
                </label>
                <label class="toggle">
                    <input type="radio" name="celibat_geo" value="0"
                           <?= (int) $profil['celibat_geo'] === 0 ? 'checked' : '' ?> required>
                    Vie commune
                </label>
            </div>

            <label class="toggle">
                <input type="checkbox" name="reg_visible" value="1"
                       <?= (int) $profil['reg_visible'] === 1 ? 'checked' : '' ?>>
                Mon profil est visible par les autres membres
            </label>

            <button type="submit" class="bouton-valider">Enregistrer</button>
        </fieldset>
    </form>

    <!-- ============================================================ -->
    <!-- Formulaire séparé pour le mot de passe (avec confirmation     -->
    <!-- du mot de passe actuel) — jamais mélangé au reste du profil   -->
    <!-- ============================================================ -->
    <form class="wizard-inscription" action="/profil/mot-de-passe/traiter" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">

        <fieldset class="etape">
            <legend>Changer mon mot de passe</legend>

            <?php if (isset($_GET['erreur_mdp'])): ?>
                <p class="message-erreur">Mot de passe actuel incorrect ou nouveaux mots de passe différents.</p>
            <?php endif; ?>

            <label for="mot_de_passe_actuel">Mot de passe actuel</label>
            <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" required>

            <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
            <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required minlength="8">

            <label for="nouveau_mot_de_passe_confirmation">Confirmer le nouveau mot de passe</label>
            <input type="password" id="nouveau_mot_de_passe_confirmation" name="nouveau_mot_de_passe_confirmation" required minlength="8">

            <button type="submit" class="bouton-valider">Changer le mot de passe</button>
        </fieldset>
    </form>

<?php else: ?>

    <!-- ============================================================ -->
    <!-- MODE LECTURE SEULE : profil d'une autre utilisatrice          -->
    <!-- Ni téléphone, ni ville, ni aucune donnée chiffrée affichée.   -->
    <!-- ============================================================ -->
    <article class="profil-public">
        <?php if ($profil['pictures_user']): ?>
            <img src="<?= htmlspecialchars($profil['pictures_user']) ?>" alt="Photo de profil de <?= htmlspecialchars($profil['account_name']) ?>">
        <?php endif; ?>

        <h1><?= htmlspecialchars($profil['account_name']) ?></h1>

        <?php if ($profil['desc_name']): ?>
            <p><?= htmlspecialchars($profil['desc_name']) ?></p>
        <?php endif; ?>

        <p><?= htmlspecialchars($profil['libelle_corps_armee']) ?><?= $profil['libelle_sous_corps'] ? ' — ' . htmlspecialchars($profil['libelle_sous_corps']) : '' ?></p>
        <p><?= htmlspecialchars($profil['libelle_situation']) ?></p>
    </article>

<?php endif; ?>

<script src="/asset/JS/profil.js"></script>