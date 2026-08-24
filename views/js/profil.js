document.addEventListener('DOMContentLoaded', () => {
    const radiosCorpsArmee = document.querySelectorAll('input[name="id_corps_armee"]');
    const groupesSousCorps = document.querySelectorAll('.sous-corps');

    radiosCorpsArmee.forEach((radio) => {
        radio.addEventListener('change', () => {
            groupesSousCorps.forEach((groupe) => {
                const estLeGroupeConcerne = groupe.dataset.parentCorps === radio.value;
                groupe.hidden = !estLeGroupeConcerne;

                if (!estLeGroupeConcerne) {
                    groupe.querySelectorAll('input[type="radio"]').forEach((input) => {
                        input.checked = false;
                    });
                }
            });
        });
    });
});