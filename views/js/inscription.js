document.addEventListener('DOMContentLoaded', () => {
    const etapes = document.querySelectorAll('.etape');
    let etapeActuelle = 1;

    function afficherEtape(numero) {
        etapes.forEach((etape) => {
            etape.hidden = parseInt(etape.dataset.step, 10) !== numero;
        });
        etapeActuelle = numero;
    }

    document.querySelectorAll('.fleche-suivant').forEach((bouton) => {
        bouton.addEventListener('click', () => {
            const etapeCourante = bouton.closest('.etape');

            if (!etapeCourante.checkValidity()) {
                etapeCourante.reportValidity();
                return;
            }

            afficherEtape(etapeActuelle + 1);
        });
    });

    document.querySelectorAll('.fleche-retour').forEach((bouton) => {
        bouton.addEventListener('click', () => {
            afficherEtape(etapeActuelle - 1);
        });
    });

    // Affiche uniquement le bloc de sous-corps correspondant au corps_armee coché
    const radiosCorpsArmee = document.querySelectorAll('input[name="id_corps_armee"]');
    const groupesSousCorps = document.querySelectorAll('.sous-corps');

    radiosCorpsArmee.forEach((radio) => {
        radio.addEventListener('change', () => {
            groupesSousCorps.forEach((groupe) => {
                const estLeGroupeConcerne = groupe.dataset.parentCorps === radio.value;
                groupe.hidden = !estLeGroupeConcerne;

                // Décoche les sous-corps des groupes masqués pour ne pas envoyer
                // une valeur incohérente avec le corps_armee choisi
                if (!estLeGroupeConcerne) {
                    groupe.querySelectorAll('input[type="radio"]').forEach((input) => {
                        input.checked = false;
                    });
                }
            });
        });
    });
});