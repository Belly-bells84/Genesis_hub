<?php
require_once __DIR__ . '/../../config/connexion.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../models/class_publication.php';

$pdo = obtenir_connexion();
$publicationRepo = new Publication($pdo);

$id_utilisateur = (int) $_SESSION['user_id'];

$publications = $publicationRepo->recup_publication_all($id_utilisateur);
$ids_publications = array_column($publications, 'id_publication');
$commentaires_par_publication = $publicationRepo->recup_commentaires_par_publications($ids_publications);
?>

<!-- Formulaire de publication                                     -->
<form class="wizard-inscription" action="/feed/publier" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">
    <fieldset class="etape">
        <legend>Publier</legend>
        <textarea name="contenu_publication" maxlength="1500" required placeholder="Quoi de neuf ?"></textarea>
        <button type="submit" class="bouton-valider">Publier</button>
    </fieldset>
</form>

<!-- ============================================================ -->
<!-- Liste des publications                                        -->
<!-- ============================================================ -->
<div class="feed">
    <?php foreach ($publications as $publication): ?>
        <article class="publication">
            <p class="publication-auteur"><?= htmlspecialchars($publication['account_name']) ?></p>
            <p class="publication-date"><?= (new DateTime($publication['date_creation_publication']))->format('d/m/Y H:i') ?></p>
            <p class="publication-contenu"><?= nl2br(htmlspecialchars($publication['contenu_publication'])) ?></p>

            <form action="/feed/aimer" method="POST" class="form-like">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">
                <input type="hidden" name="id_publication" value="<?= (int) $publication['id_publication'] ?>">
                <button type="submit" class="bouton-like <?= $publication['deja_aime'] ? 'bouton-like-actif' : '' ?>">
                    <?= $publication['deja_aime'] ? '❤️' : '🤍' ?> <?= (int) $publication['nb_likes'] ?>
                </button>
            </form>

            <div class="commentaires">
                <p class="nb-commentaires"><?= (int) $publication['nb_commentaires'] ?> commentaires</p>

                <?php foreach ($commentaires_par_publication[$publication['id_publication']] ?? [] as $commentaire): ?>
                    <p class="commentaire">
                        <strong><?= htmlspecialchars($commentaire['account_name']) ?></strong>
                        <?= htmlspecialchars($commentaire['contenu_commentaire']) ?>
                    </p>
                <?php endforeach; ?>

                <form action="/feed/commenter" method="POST" class="form-commentaire">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generer_jeton_csrf()) ?>">
                    <input type="hidden" name="id_publication" value="<?= (int) $publication['id_publication'] ?>">
                    <input type="text" name="contenu_commentaire" maxlength="1500" required placeholder="Ajouter un commentaire...">
                    <button type="submit" class="bouton-commenter">Envoyer</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (empty($publications)): ?>
        <p>Aucune publication pour l'instant.</p>
    <?php endif; ?>
</div>