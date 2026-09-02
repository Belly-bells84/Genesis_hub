document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // Gestion du like en AJAX
    // ============================================================
    document.querySelectorAll('.form-like').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const bouton = form.querySelector('.bouton-like');
            const formData = new FormData(form);

            try {
                const reponse = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                });

                if (!reponse.ok) {
                    console.error('Erreur lors du like.', await reponse.text());
                    return;
                }

                const donnees = await reponse.json();

                bouton.classList.toggle('bouton-like-actif', donnees.deja_aime);
                bouton.textContent = `${donnees.deja_aime ? '❤️' : '🤍'} ${donnees.nb_likes}`;
            } catch (erreur) {
                console.error('Erreur réseau lors du like :', erreur);
            }
        });
    });

    // ============================================================
    // Gestion du commentaire en AJAX
    // ============================================================
    document.querySelectorAll('.form-commentaire').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const article = form.closest('.publication');
            const conteneurCommentaires = form.closest('.commentaires');
            const compteur = conteneurCommentaires.querySelector('.nb-commentaires');
            const champTexte = form.querySelector('[name="contenu_commentaire"]');
            const formData = new FormData(form);

            try {
                const reponse = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                });

                if (!reponse.ok) {
                    console.error("Erreur lors de l'ajout du commentaire.", await reponse.text());
                    return;
                }

                const commentaire = await reponse.json();

                // On construit le nouvel élément via des noeuds texte
                // (jamais innerHTML avec du contenu utilisateur) pour
                // éviter toute injection HTML (XSS).
                const p = document.createElement('p');
                p.className = 'commentaire';

                const strong = document.createElement('strong');
                strong.textContent = commentaire.account_name;

                p.appendChild(strong);
                p.appendChild(document.createTextNode(' ' + commentaire.contenu_commentaire));

                form.insertAdjacentElement('beforebegin', p);

                const nbActuel = parseInt(compteur.textContent, 10) || 0;
                compteur.textContent = `${nbActuel + 1} commentaires`;

                champTexte.value = '';
            } catch (erreur) {
                console.error('Erreur réseau lors du commentaire :', erreur);
            }
        });
    });
});