/**
 * @jest-environment jsdom
 */

const { construireElementCommentaire, construireElementMessage, mettreAJourBoutonLike } = require('./dom_utils');

describe('construireElementCommentaire', () => {
    test('construit un <p class="commentaire"> avec le pseudo en gras et le contenu à la suite', () => {
        const element = construireElementCommentaire({
            account_name: 'Marion',
            contenu_commentaire: 'Super publication !',
        });

        expect(element.tagName).toBe('P');
        expect(element.className).toBe('commentaire');
        expect(element.querySelector('strong').textContent).toBe('Marion');
        expect(element.textContent).toBe('Marion Super publication !');
    });

    test('neutralise un contenu malveillant (XSS) en le traitant comme du texte, jamais du HTML', () => {
        const element = construireElementCommentaire({
            account_name: 'Attaquante',
            contenu_commentaire: '<img src=x onerror="alert(1)">',
        });

        // Le tag ne doit jamais être interprété : aucun <img> ne doit exister
        // dans l'élément construit, le contenu doit rester du texte brut.
        expect(element.querySelector('img')).toBeNull();
        expect(element.textContent).toContain('<img src=x onerror="alert(1)">');
    });
});

describe('construireElementMessage', () => {
    const message = {
        account_user_emetteur: 5,
        id_message_private: 42,
        contenu_message: 'Salut !',
        date_envoi_message: '2026-09-11 10:30:00',
    };

    test('applique la classe message-envoye quand l\'émetteur est l\'utilisatrice courante', () => {
        const element = construireElementMessage(message, 5);

        expect(element.className).toBe('message message-envoye');
    });

    test('applique la classe message-recu quand l\'émetteur est quelqu\'un d\'autre', () => {
        const element = construireElementMessage(message, 99);

        expect(element.className).toBe('message message-recu');
    });

    test('associe le bon data-id-message, réutilisé pour éviter les doublons côté polling', () => {
        const element = construireElementMessage(message, 5);

        expect(element.dataset.idMessage).toBe('42');
    });

    test('contient le contenu du message en texte brut', () => {
        const element = construireElementMessage(message, 5);

        expect(element.textContent).toContain('Salut !');
    });
});

describe('mettreAJourBoutonLike', () => {
    test('affiche un coeur plein et ajoute la classe active quand deja_aime est vrai', () => {
        const bouton = document.createElement('button');
        bouton.className = 'bouton-like';

        mettreAJourBoutonLike(bouton, { deja_aime: true, nb_likes: 3 });

        expect(bouton.classList.contains('bouton-like-actif')).toBe(true);
        expect(bouton.textContent).toBe('❤️ 3');
    });

    test('affiche un coeur vide et retire la classe active quand deja_aime est faux', () => {
        const bouton = document.createElement('button');
        bouton.className = 'bouton-like bouton-like-actif';

        mettreAJourBoutonLike(bouton, { deja_aime: false, nb_likes: 2 });

        expect(bouton.classList.contains('bouton-like-actif')).toBe(false);
        expect(bouton.textContent).toBe('🤍 2');
    });
});