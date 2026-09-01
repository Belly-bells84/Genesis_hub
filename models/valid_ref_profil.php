<?php

/**
 * Valide les références de corps d'armée / sous-corps / situation
 * envoyées par le formulaire, en vérifiant systématiquement leur
 * existence réelle en base (on ne fait jamais confiance à un id
 * envoyé par le navigateur).
 *
 * Utilisée à la fois par traiter_inscription.php et traiter_profil.php
 * pour éviter la duplication de cette logique — et le risque que les
 * deux formulaires finissent par appliquer des règles différentes.
 *
 * @param PDO $pdo
 * @param int|null $id_corps_armee
 * @param int|null $id_sous_corps_armee
 * @param int|null $id_situation
 * @param array $erreurs Tableau d'erreurs, complété par référence
 */
function valid_ref_profil(
    PDO $pdo,
    ?int $id_corps_armee,
    ?int $id_sous_corps_armee,
    ?int $id_situation,
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

    $stmt_situation = $pdo->prepare('SELECT 1 FROM situation_relationship WHERE id_situation = ?');
    $stmt_situation->execute([$id_situation]);
    if (!$stmt_situation->fetch()) {
        $erreurs[] = 'Situation relationnelle invalide.';
    }
}