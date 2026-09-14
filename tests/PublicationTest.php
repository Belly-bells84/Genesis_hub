<?php

use PHPUnit\Framework\TestCase;

final class PublicationTest extends TestCase
{
    private PDO $pdo;
    private Publication $publicationRepo;

    protected function setUp(): void
    {
        $this->pdo = creer_pdo_test();
        $this->publicationRepo = new Publication($this->pdo);

        // Deux utilisatrices de test
        $this->pdo->exec("INSERT INTO account_user (id, account_name) VALUES (1, 'Belly'), (2, 'Marion')");
    }

    public function test_creer_une_publication_texte_seul(): void
    {
        $this->publicationRepo->creer(1, 'Bonjour tout le monde');

        $publications = $this->publicationRepo->recup_publication_all(1);

        $this->assertCount(1, $publications);
        $this->assertSame('Bonjour tout le monde', $publications[0]['contenu_publication']);
        $this->assertSame('Belly', $publications[0]['account_name']);
        $this->assertNull($publications[0]['chemin_media']);
    }

    public function test_creer_une_publication_avec_media(): void
    {
        $this->publicationRepo->creer(1, 'Ma photo', '/uploads/media/abc.jpg', 'image');

        $publications = $this->publicationRepo->recup_publication_all(1);

        $this->assertSame('/uploads/media/abc.jpg', $publications[0]['chemin_media']);
        $this->assertSame('image', $publications[0]['type_media']);
    }

    public function test_existe_retourne_vrai_pour_une_publication_existante(): void
    {
        $this->publicationRepo->creer(1, 'Test');
        $publications = $this->publicationRepo->recup_publication_all(1);
        $id = (int) $publications[0]['id_publication'];

        $this->assertTrue($this->publicationRepo->existe($id));
    }

    public function test_existe_retourne_faux_pour_un_id_inexistant(): void
    {
        $this->assertFalse($this->publicationRepo->existe(9999));
    }

    public function test_basculer_like_ajoute_puis_retire_le_like(): void
    {
        $this->publicationRepo->creer(1, 'Une publication');
        $id_publication = (int) $this->publicationRepo->recup_publication_all(1)[0]['id_publication'];

        // Premier appel : ajoute le like
        $resultat = $this->publicationRepo->basculerLike(2, $id_publication);
        $this->assertTrue($resultat['deja_aime']);
        $this->assertSame(1, $resultat['nb_likes']);

        // Deuxième appel : retire le like
        $resultat = $this->publicationRepo->basculerLike(2, $id_publication);
        $this->assertFalse($resultat['deja_aime']);
        $this->assertSame(0, $resultat['nb_likes']);
    }

    public function test_recup_publication_all_reflete_letat_deja_aime_pour_le_lecteur(): void
    {
        $this->publicationRepo->creer(1, 'Une publication');
        $id_publication = (int) $this->publicationRepo->recup_publication_all(1)[0]['id_publication'];

        $this->publicationRepo->basculerLike(2, $id_publication);

        // Vue par l'utilisatrice 2 (qui a liké) : deja_aime doit être vrai
        $vue_par_2 = $this->publicationRepo->recup_publication_all(2)[0];
        $this->assertEquals(1, $vue_par_2['deja_aime']);

        // Vue par l'utilisatrice 1 (qui n'a pas liké) : deja_aime doit être faux
        $vue_par_1 = $this->publicationRepo->recup_publication_all(1)[0];
        $this->assertEquals(0, $vue_par_1['deja_aime']);
    }

    public function test_creer_commentaire_et_le_retrouver(): void
    {
        $this->publicationRepo->creer(1, 'Une publication');
        $id_publication = (int) $this->publicationRepo->recup_publication_all(1)[0]['id_publication'];

        $this->publicationRepo->creerCommentaire(2, $id_publication, 'Super publication !');

        $commentaires = $this->publicationRepo->recup_commentaires_par_publications([$id_publication]);

        $this->assertCount(1, $commentaires[$id_publication]);
        $this->assertSame('Super publication !', $commentaires[$id_publication][0]['contenu_commentaire']);
        $this->assertSame('Marion', $commentaires[$id_publication][0]['account_name']);
    }

    public function test_est_auteure_distingue_correctement_lauteure_et_les_autres(): void
    {
        $this->publicationRepo->creer(1, 'Une publication');
        $id_publication = (int) $this->publicationRepo->recup_publication_all(1)[0]['id_publication'];

        $this->assertTrue($this->publicationRepo->estAuteure(1, $id_publication));
        $this->assertFalse($this->publicationRepo->estAuteure(2, $id_publication));
    }

    public function test_recup_chemin_media_retourne_null_si_publication_texte_seul(): void
    {
        $this->publicationRepo->creer(1, 'Texte seul');
        $id_publication = (int) $this->publicationRepo->recup_publication_all(1)[0]['id_publication'];

        $this->assertNull($this->publicationRepo->recupCheminMedia($id_publication));
    }

    public function test_supprimer_une_publication_la_retire_du_feed(): void
    {
        $this->publicationRepo->creer(1, 'À supprimer');
        $id_publication = (int) $this->publicationRepo->recup_publication_all(1)[0]['id_publication'];

        $this->publicationRepo->supprimer($id_publication);

        $this->assertFalse($this->publicationRepo->existe($id_publication));
        $this->assertCount(0, $this->publicationRepo->recup_publication_all(1));
    }
}