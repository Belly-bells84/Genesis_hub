document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // Recherche d'utilisatrices (page liste des messages)
    // ============================================================
    const champRecherche = document.getElementById('recherche-utilisateur');
    const listeResultats = document.getElementById('resultats-recherche');

    if (champRecherche) {
        let minuteurRecherche = null;

        champRecherche.addEventListener('input', () => {
            clearTimeout(minuteurRecherche);
            const terme = champRecherche.value.trim();

            if (terme.length < 2) {
                listeResultats.innerHTML = '';
                return;
            }

            // On attend une courte pause après la frappe avant d'interroger
            // le serveur, pour ne pas envoyer une requête à chaque lettre tapée.
            minuteurRecherche = setTimeout(async () => {
                try {
                    const reponse = await fetch(`/messages/rechercher?q=${encodeURIComponent(terme)}`);

                    if (!reponse.ok) {
                        console.error('Erreur lors de la recherche.', await reponse.text());
                        return;
                    }

                    const utilisateurs = await reponse.json();

                    listeResultats.innerHTML = '';

                    utilisateurs.forEach((utilisateur) => {
                        const li = document.createElement('li');
                        const a = document.createElement('a');
                        a.href = `/messages/${utilisateur.id}`;
                        a.textContent = utilisateur.account_name;
                        li.appendChild(a);
                        listeResultats.appendChild(li);
                    });
                } catch (erreur) {
                    console.error('Erreur réseau lors de la recherche :', erreur);
                }
            }, 300);
        });
    }

    // ============================================================
    // Conversation : envoi de message + polling des nouveaux messages
    // ============================================================
    const conteneurConversation = document.querySelector('.conversation-page');

    if (conteneurConversation) {
        const idContact = parseInt(conteneurConversation.dataset.idContact, 10);
        const idUtilisateur = parseInt(conteneurConversation.dataset.idUtilisateur, 10);
        let dernierId = parseInt(conteneurConversation.dataset.dernierId, 10) || 0;

        const filMessages = document.getElementById('fil-messages');
        const formulaire = document.getElementById('form-envoi-message');

        /**
         * Ajoute un message au fil de discussion. On construit l'élément
         * avec des noeuds texte (jamais innerHTML avec du contenu venant
         * de la base ou d'un autre utilisateur) pour éviter toute
         * injection HTML (XSS) via le contenu d'un message.
         */
        function ajouterMessage(message) {
            // Évite d'afficher deux fois le même message (le polling
            // pourrait le renvoyer juste après qu'on l'ait ajouté nous-même
            // en l'envoyant).
            if (filMessages.querySelector(`[data-id-message="${message.id_message_private}"]`)) {
                return;
            }

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

            filMessages.appendChild(p);
            filMessages.scrollTop = filMessages.scrollHeight;

            if (message.id_message_private > dernierId) {
                dernierId = message.id_message_private;
            }
        }

        formulaire.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(formulaire);
            const champTexte = formulaire.querySelector('[name="contenu_message"]');

            try {
                const reponse = await fetch('/messages/envoyer', {
                    method: 'POST',
                    body: formData,
                });

                if (!reponse.ok) {
                    console.error("Erreur lors de l'envoi du message.", await reponse.text());
                    return;
                }

                const message = await reponse.json();
                ajouterMessage(message);
                champTexte.value = '';
                champTexte.focus();
            } catch (erreur) {
                console.error("Erreur réseau lors de l'envoi :", erreur);
            }
        });

        /**
         * Interroge le serveur pour récupérer les messages reçus depuis
         * le dernier connu. Simule le temps réel sans WebSocket — un
         * compromis pragmatique pour une stack PHP/WAMP classique.
         */
        async function verifierNouveauxMessages() {
            try {
                const reponse = await fetch(`/messages/nouveaux?id_contact=${idContact}&depuis_id=${dernierId}`);

                if (!reponse.ok) {
                    return;
                }

                const nouveauxMessages = await reponse.json();
                nouveauxMessages.forEach(ajouterMessage);
            } catch (erreur) {
                console.error('Erreur réseau lors du polling :', erreur);
            }
        }

        setInterval(verifierNouveauxMessages, 3000);
    }
});