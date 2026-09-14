<?php
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../config/chiffrement.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../models/class_user.php';
require_once __DIR__ . '/../../models/class_referentiel.php';
$pdo = obtenir_connexion();
$userRepo = new User($pdo);
$referentielRepo = new Referentiel($pdo);

$est_propre_profil = ($id_profil_consulte === (int) $_SESSION['user_id']);

$profil = $userRepo->recupParId($id_profil_consulte);

if (!$profil || (!$est_propre_profil && (int) $profil['reg_visible'] === 0)) {
    http_response_code(404);
    echo '<p>Profil introuvable.</p>';
    return;
}

if ($est_propre_profil) {
    $phone_en_clair = $profil['phone_user'] !== null ? dechiffrer($profil['phone_user']) : '';
    $city_en_clair = $profil['city_user'] !== null ? dechiffrer($profil['city_user']) : '';

    $corps_armee_liste = $referentielRepo->recupCorpsArmee();
    $sous_corps_liste = $referentielRepo->recupSousCorpsArmee();
    $situation_liste = $referentielRepo->recupSituations();
    $sous_situation_liste = $referentielRepo->recupSousSituations();
}
?>

<link rel="stylesheet" href="views/css/profil_style.css">

<main class="profil-main">

<?php if ($est_propre_profil): ?>
<script src="/asset/JS/profil.js" defer></script>

    <form class="wizard-inscription profil-carte" action="/profil/traiter" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">

        <fieldset class="etape profil-fieldset">
            <legend class="profil-legende-cachee">Mon profil</legend>

            <?php if (isset($_GET['succes'])): ?>
                <p class="message-succes profil-message profil-message--succes">Profil mis à jour.</p>
            <?php endif; ?>

            <div class="profil-en-tete">
                <div class="profil-avatar">
                    <div class="profil-avatar__cadre">
                        <?php if ($profil['pictures_user']): ?>
                            <img class="profil-avatar__img" src="<?= htmlspecialchars($profil['pictures_user']) ?>" alt="Photo de profil de <?= htmlspecialchars($profil['account_name']) ?>">
                        <?php else: ?>
                            <span class="profil-avatar__initiale"><?= htmlspecialchars(mb_strtoupper(mb_substr($profil['account_name'], 0, 1))) ?></span>
                        <?php endif; ?>
                    </div>
                    <label for="pictures_user" class="profil-avatar__edit" title="Changer la photo de profil">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l1.5-2h7L17 7h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13" r="3.4"/></svg>
                    </label>
                    <input type="file" id="pictures_user" name="pictures_user" accept="image/png, image/jpeg, image/webp" class="profil-avatar__input">
                </div>

                <div class="profil-identite">
                    <label for="account_name" class="profil-champ-libelle">Pseudo</label>
                    <div class="profil-handle">
                        <span class="profil-handle__arobase">@</span>
                        <input type="text" id="account_name" name="account_name" required maxlength="150"
                               value="<?= htmlspecialchars($profil['account_name']) ?>">
                    </div>
                </div>
            </div>

            <div class="profil-bio">
                <label for="desc_name" class="profil-champ-libelle">Bio</label>
                <textarea id="desc_name" name="desc_name" maxlength="1500" rows="3"
                          placeholder="Description / biographie de l'utilisatrice·teur"><?= htmlspecialchars($profil['desc_name'] ?? '') ?></textarea>
            </div>

            <div class="profil-champs">
                <div class="champ">
                    <label for="work_user" class="profil-champ-libelle">Métier</label>
                    <input type="text" id="work_user" name="work_user" maxlength="150"
                           value="<?= htmlspecialchars($profil['work_user'] ?? '') ?>">
                </div>

                <div class="champ">
                    <label for="phone_user" class="profil-champ-libelle">Téléphone</label>
                    <input type="tel" id="phone_user" name="phone_user" maxlength="20"
                           value="<?= htmlspecialchars($phone_en_clair) ?>">
                </div>

                <div class="champ">
                    <label for="city_user" class="profil-champ-libelle">Ville</label>
                    <input type="text" id="city_user" name="city_user" maxlength="150"
                           value="<?= htmlspecialchars($city_en_clair) ?>">
                </div>
            </div>
        </fieldset>

        <fieldset class="etape profil-fieldset">
            <legend class="profil-section-titre">Corps d'armée</legend>

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

            <legend class="profil-section-titre profil-section-titre--espace">Situation</legend>

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

            <?php foreach ($situation_liste as $situation): ?>
                <div class="toggle-groupe sous-situation"
                     data-parent-situation="<?= htmlspecialchars($situation['id_situation']) ?>"
                     <?= (int) $situation['id_situation'] !== (int) $profil['id_situation'] ? 'hidden' : '' ?>>
                    <?php foreach ($sous_situation_liste as $sous_sit): ?>
                        <?php if ($sous_sit['id_situation'] == $situation['id_situation']): ?>
                            <label class="toggle">
                                <input type="radio" name="id_sous_situation"
                                       value="<?= htmlspecialchars($sous_sit['id_sous_situation']) ?>"
                                       <?= (int) $sous_sit['id_sous_situation'] === (int) ($profil['id_sous_situation'] ?? 0) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($sous_sit['libelle_sous_situation']) ?>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <label class="toggle profil-switch">
                <input type="checkbox" name="reg_visible" value="1"
                       <?= (int) $profil['reg_visible'] === 1 ? 'checked' : '' ?>>
                <span class="profil-switch__piste" aria-hidden="true"></span>
                <span class="profil-switch__texte">Mon profil est visible par les autres membres</span>
            </label>

            <button type="submit" class="bouton-valider profil-bouton">Enregistrer</button>
        </fieldset>
    </form>

    <form class="wizard-inscription profil-carte profil-carte--secondaire" action="/profil/mot-de-passe/traiter" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">

        <fieldset class="etape profil-fieldset">
            <legend class="profil-section-titre">Changer mon mot de passe</legend>

            <?php if (isset($_GET['erreur_mdp'])): ?>
                <p class="message-erreur profil-message profil-message--erreur">Mot de passe actuel incorrect ou nouveaux mots de passe différents.</p>
            <?php endif; ?>

            <div class="profil-champs">
                <div class="champ">
                    <label for="mot_de_passe_actuel" class="profil-champ-libelle">Mot de passe actuel</label>
                    <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" required>
                </div>

                <div class="champ">
                    <label for="nouveau_mot_de_passe" class="profil-champ-libelle">Nouveau mot de passe</label>
                    <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required minlength="8">
                </div>

                <div class="champ">
                    <label for="nouveau_mot_de_passe_confirmation" class="profil-champ-libelle">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="nouveau_mot_de_passe_confirmation" name="nouveau_mot_de_passe_confirmation" required minlength="8">
                </div>
            </div>

            <button type="submit" class="bouton-valider profil-bouton profil-bouton--discret">Changer le mot de passe</button>
        </fieldset>
    </form>

<?php else: ?>

    <article class="profil-public profil-carte">
        <div class="profil-en-tete">
            <div class="profil-avatar">
                <div class="profil-avatar__cadre">
                    <?php if ($profil['pictures_user']): ?>
                        <img class="profil-avatar__img" src="<?= htmlspecialchars($profil['pictures_user']) ?>" alt="Photo de profil de <?= htmlspecialchars($profil['account_name']) ?>">
                    <?php else: ?>
                        <span class="profil-avatar__initiale"><?= htmlspecialchars(mb_strtoupper(mb_substr($profil['account_name'], 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profil-identite">
                <h1 class="profil-handle profil-handle--statique">
                    <span class="profil-handle__arobase">@</span><?= htmlspecialchars($profil['account_name']) ?>
                </h1>
            </div>
        </div>

        <?php if ($profil['desc_name']): ?>
            <p class="profil-bio profil-bio--statique"><?= htmlspecialchars($profil['desc_name']) ?></p>
        <?php endif; ?>

        <div class="profil-tags">
            <span class="profil-tag">
                <?= htmlspecialchars($profil['libelle_corps_armee']) ?><?= $profil['libelle_sous_corps'] ? ' — ' . htmlspecialchars($profil['libelle_sous_corps']) : '' ?>
            </span>
            <span class="profil-tag"><?= htmlspecialchars($profil['libelle_situation']) ?></span>
        </div>
    </article>

<?php endif; ?>

</main>