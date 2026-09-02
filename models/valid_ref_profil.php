<?php

/**
 * Valide les références de corps d'armée / sous-corps / situation /
 * sous-situation envoyées par le formulaire, en vérifiant systématiquement
 * leur existence réelle en base (on ne fait jamais confiance à un id
 * envoyé par le navigateur).
 *
 * Utilisée à la fois par traiter_inscription.php et traiter_profil.php
 * pour éviter la duplication de cette logique.
 *
 * @param PDO $pdo
 * @param int|null $id_corps_armee
 * @param int|null $id_sous_corps_armee
 * @param int|null $id_situation
 * @param int|null $id_sous_situation
 * @param array $erreurs Tableau d'erreurs, complété par référence
 */
function valid_ref_profil(
    PDO $pdo,
    ?int $id_corps_armee,
    ?int $id_sous_corps_armee,
    ?int $id_situation,
    ?int $id_sous_situation,
    array &$erreurs
): void {
    $stmt_corps = $pdo->prepare('SELECT sous_corps_obligatoire FROM corps_armee WHERE id_corps_armee = ?');
    $stmt_corps->execute([$id_corps_armee]);
    $corps = $stmt_corps->fetch();

    if (!$corps) {
        $erreurs[] = 'Corps d\'armée invalide.';
    } else {
        if ((int) $corps['sous_corps_obligatoire'] === 1 && $id_sous_corps_armee === null) {
            $erreurs[] = 'Merci de préciser le sous-corps.';
        }

        if ($id_sous_corps_armee !== null) {
            $stmt_sous = $pdo->prepare('SELECT 1 FROM sous_corps_armee WHERE id_sous_corps_armee = ? AND id_corps_armee = ?');
            $stmt_sous->execute([$id_sous_corps_armee, $id_corps_armee]);
            if (!$stmt_sous->fetch()) {
                $erreurs[] = 'Sous-corps invalide pour ce corps d\'armée.';
            }
        }
    }

    $stmt_situation = $pdo->prepare('SELECT sous_situation_obligatoire FROM situation_relationship WHERE id_situation = ?');
    $stmt_situation->execute([$id_situation]);
    $situation = $stmt_situation->fetch();

    if (!$situation) {
        $erreurs[] = 'Situation relationnelle invalide.';
    } else {
        if ((int) $situation['sous_situation_obligatoire'] === 1 && $id_sous_situation === null) {
            $erreurs[] = 'Merci de préciser votre situation géographique.';
        }

        if ($id_sous_situation !== null) {
            $stmt_sous_sit = $pdo->prepare('SELECT 1 FROM sous_situation WHERE id_sous_situation = ? AND id_situation = ?');
            $stmt_sous_sit->execute([$id_sous_situation, $id_situation]);
            if (!$stmt_sous_sit->fetch()) {
                $erreurs[] = 'Situation géographique invalide pour cette situation.';
            }
        }
    }
}