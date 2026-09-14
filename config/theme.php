<?php

/**
 * Convertit la valeur stockée dans account_user.theme ('feminin', 'masculin',
 * 'neutre') vers le nom de classe CSS correspondant (.color_women,
 * .color_men, .neutral, définies dans style.css).
 */
function theme_vers_classe_css(string $theme): string
{
    return match ($theme) {
        'feminin' => 'color_women',
        'masculin' => 'color_men',
        default => 'neutral',
    };
}