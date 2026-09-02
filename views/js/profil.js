document.addEventListener('DOMContentLoaded', () => {
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