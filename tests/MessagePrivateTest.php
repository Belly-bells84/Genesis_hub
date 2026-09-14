<?php

use PHPUnit\Framework\TestCase;

final class MessagePrivateTest extends TestCase
{
    private PDO $pdo;
    private MessagePrivate $messageRepo;

    protected function setUp(): void
    {
        $this->pdo = creer_pdo_test();
        $this->messageRepo = new MessagePrivate($this->pdo);

        $this->pdo->exec("INSERT INTO account_user (id, account_name) VALUES (1, 'Belly'), (2, 'Marion'), (3, 'Sofia')");
    }

    public function test_utilisateur_existe(): void
    {
        $this->assertTrue($this->messageRepo->utilisateurExiste(1));
        $this->assertFalse($this->messageRepo->utilisateurExiste(999));
    }

    public function test_envoyer_un_message_et_le_retrouver_dans_la_conversation(): void
    {
        $this->messageRepo->envoyerMessage(1, 2, 'Salut Marion !');

        $messages = $this->messageRepo->recupMessages(1, 2);

        $this->assertCount(1, $messages);
        $this->assertSame('Salut Marion !', $messages[0]['contenu_message']);
    }

    public function test_recup_messages_fonctionne_dans_les_deux_sens(): void
    {
        $this->messageRepo->envoyerMessage(1, 2, 'Message de 1 vers 2');
        $this->messageRepo->envoyerMessage(2, 1, 'Réponse de 2 vers 1');

        // La conversation doit contenir les deux messages, peu importe qui interroge
        $vue_par_1 = $this->messageRepo->recupMessages(1, 2);
        $vue_par_2 = $this->messageRepo->recupMessages(2, 1);

        $this->assertCount(2, $vue_par_1);
        $this->assertCount(2, $vue_par_2);
    }

    public function test_recup_messages_avec_depuis_id_ne_retourne_que_les_nouveaux(): void
    {
        $id_premier = $this->messageRepo->envoyerMessage(1, 2, 'Premier message');
        $this->messageRepo->envoyerMessage(1, 2, 'Deuxième message');

        // Utilisé par le sondage (polling) côté JS : ne doit renvoyer que
        // ce qui est arrivé après le premier message.
        $nouveaux = $this->messageRepo->recupMessages(1, 2, $id_premier);

        $this->assertCount(1, $nouveaux);
        $this->assertSame('Deuxième message', $nouveaux[0]['contenu_message']);
    }

    public function test_marquer_comme_lu_ne_touche_que_les_messages_du_bon_expediteur(): void
    {
        $this->messageRepo->envoyerMessage(2, 1, 'De Marion vers Belly');
        $this->messageRepo->envoyerMessage(3, 1, 'De Sofia vers Belly');

        // Belly ouvre sa conversation avec Marion uniquement
        $this->messageRepo->marquerCommeLu(1, 2);

        $messages = $this->messageRepo->recupMessages(1, 3);
        $this->assertEquals(0, $messages[0]['message_lu'], 'Le message de Sofia ne doit pas être affecté.');
    }

    public function test_rechercher_utilisateurs_trouve_par_sous_chaine(): void
    {
        $resultats = $this->messageRepo->rechercherUtilisateurs('ari', 1);

        $this->assertCount(1, $resultats);
        $this->assertSame('Marion', $resultats[0]['account_name']);
    }

    public function test_rechercher_utilisateurs_inclut_desormais_soi_meme(): void
    {
        // Rappel fonctionnel : l'auto-message est autorisé (bloc-notes
        // personnel), la recherche ne doit donc plus exclure l'utilisatrice
        // courante des résultats.
        $resultats = $this->messageRepo->rechercherUtilisateurs('Belly', 1);

        $this->assertCount(1, $resultats);
        $this->assertSame(1, (int) $resultats[0]['id']);
    }

    public function test_recup_conversations_liste_les_bons_contacts_avec_dernier_message(): void
    {
        $this->messageRepo->envoyerMessage(1, 2, 'Premier message à Marion');
        $this->messageRepo->envoyerMessage(2, 1, 'Réponse de Marion');
        $this->messageRepo->envoyerMessage(1, 3, 'Message à Sofia');

        $conversations = $this->messageRepo->recupConversations(1);

        $this->assertCount(2, $conversations, 'Belly a deux conversations distinctes (Marion et Sofia).');

        $noms = array_column($conversations, 'account_name');
        $this->assertContains('Marion', $noms);
        $this->assertContains('Sofia', $noms);
    }

    public function test_recup_conversations_compte_les_messages_non_lus(): void
    {
        $this->messageRepo->envoyerMessage(2, 1, 'Message non lu 1');
        $this->messageRepo->envoyerMessage(2, 1, 'Message non lu 2');

        $conversations = $this->messageRepo->recupConversations(1);

        $this->assertSame(2, (int) $conversations[0]['nb_non_lus']);

        $this->messageRepo->marquerCommeLu(1, 2);

        $conversations_apres_lecture = $this->messageRepo->recupConversations(1);
        $this->assertSame(0, (int) $conversations_apres_lecture[0]['nb_non_lus']);
    }
}