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

    // Fonction générique : affiche uniquement le sous-groupe correspondant
    // à la valeur cochée dans un groupe de radios parent (réutilisée pour
    // corps_armee -> sous-corps ET situation -> sous-situation).
    function activerRevelationConditionnelle(nomChampParent, classeGroupeEnfant, attributParent) {
        const radiosParent = document.querySelectorAll(`input[name="${nomChampParent}"]`);
        const groupesEnfants = document.querySelectorAll(`.${classeGroupeEnfant}`);

        radiosParent.forEach((radio) => {
            radio.addEventListener('change', () => {
                groupesEnfants.forEach((groupe) => {
                    const estLeGroupeConcerne = groupe.dataset[attributParent] === radio.value;
                    groupe.hidden = !estLeGroupeConcerne;

                    if (!estLeGroupeConcerne) {
                        groupe.querySelectorAll('input[type="radio"]').forEach((input) => {
                            input.checked = false;
                        });
                    }
                });
            });
        });
    }

    activerRevelationConditionnelle('id_corps_armee', 'sous-corps', 'parentCorps');
    activerRevelationConditionnelle('id_situation', 'sous-situation', 'parentSituation');
});