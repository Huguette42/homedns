<?php

declare(strict_types=1);

namespace Homedns;

/**
 * Lien de tri pour les en-têtes de colonnes
 */
function sortLink(string $field, string $label, string $currentSort, string $currentDir): string
{
    $newDir = ($currentSort === $field && $currentDir === 'asc') ? 'desc' : 'asc';
    $arrow = ($currentSort === $field) ? ($currentDir === 'asc' ? ' ▲' : ' ▼') : '';

    $query = http_build_query([
        'sort' => $field,
        'dir' => $newDir,
        'search' => $_GET['search'] ?? '',
        'view' => $_GET['view'] ?? 'cards',
    ]);

    return "<a href=\"?{$query}\" class=\"sort-link\">{$label}{$arrow}</a>";
}

/**
 * Crée une URL avec les paramètres actuels
 */
function buildUrl(array $params = []): string
{
    $defaults = [
        'search' => $_GET['search'] ?? '',
        'sort' => $_GET['sort'] ?? 'name',
        'dir' => $_GET['dir'] ?? 'asc',
        'view' => $_GET['view'] ?? 'cards',
    ];

    $merged = array_merge($defaults, $params);
    return '?' . http_build_query($merged);
}

/**
 * Badge pour le statut DNS
 */
function statusBadge(bool $up, ?string $address = null): string
{
    if (!$up) {
        return '<span class="badge badge-ko">KO</span>';
    }

    return '<span class="badge badge-ok">OK</span>' . ($address ? " <code>$address</code>" : '');
}
