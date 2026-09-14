/**
 * Fonctions pures de construction du DOM, extraites de feed.js et
 * messages.js pour être testables indépendamment. Compatible à la fois
 * navigateur (exposé sur window.GenesisUtils, aucun bundler requis) et
 * Node/Jest (module.exports), sans dépendance à un outil de build.
 */
(function (global) {
    /**
     * Construit l'élément <p> d'un nouveau commentaire (fil du feed).
     * Utilise exclusivement des nœuds texte pour le contenu utilisateur
     * (jamais innerHTML), afin d'empêcher toute injection HTML/JS (XSS)
     * via le pseudo ou le contenu du commentaire.
     */
    function construireElementCommentaire(commentaire) {
        const p = document.createElement('p');
        p.className = 'commentaire';

        const strong = document.createElement('strong');
        strong.textContent = commentaire.account_name;

        p.appendChild(strong);
        p.appendChild(document.createTextNode(' ' + commentaire.contenu_commentaire));

        return p;
    }

    /**
     * Construit l'élément <p> d'un nouveau message (fil de conversation).
     * La classe CSS distingue message envoyé / reçu selon l'émetteur.
     */
    function construireElementMessage(message, idUtilisateur) {
        const p = document.createElement('p');
        const estEnvoye = parseInt(message.account_user_emetteur, 10) === idUtilisateur;
        p.className = 'message ' + (estEnvoye ? 'message-envoye' : 'message-recu');
        p.dataset.idMessage = message.id_message_private;

        p.appendChild(document.createTextNode(message.contenu_message));
        p.appendChild(document.createElement('br'));

        const span = document.createElement('span');
        span.className = 'message-date';
        span.textContent = new Date(message.date_envoi_message.replace(' ', 'T')).toLocaleString('fr-FR');
        p.appendChild(span);

        return p;
    }

    /**
     * Met à jour l'apparence du bouton like après une réponse du serveur.
     */
    function mettreAJourBoutonLike(bouton, donnees) {
        bouton.classList.toggle('bouton-like-actif', donnees.deja_aime);
        bouton.textContent = `${donnees.deja_aime ? '❤️' : '🤍'} ${donnees.nb_likes}`;
    }

    const api = { construireElementCommentaire, construireElementMessage, mettreAJourBoutonLike };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = api;
    } else {
        global.GenesisUtils = api;
    }
})(typeof window !== 'undefined' ? window : globalThis);