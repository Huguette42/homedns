<?php

declare(strict_types=1);

require_once __DIR__ . '/src/DnsResolver.php';
require_once __DIR__ . '/src/helpers.php';

use Homedns\DnsResolver;

$domains = [
    ['name' => 'Boris Perier', 'domain' => 'perier.local'],
    ['name' => 'Loris Petitjean', 'domain' => 'petitjean.local'],
    ['name' => 'Boris Junique', 'domain' => 'junique.local'],
    ['name' => 'Nicolas Dumont', 'domain' => 'dumont.local'],
    ['name' => 'Test Server', 'domain' => 'localhost'],
];

$search = trim((string) ($_GET['search'] ?? ''));
$view = ($_GET['view'] ?? 'cards') === 'table' ? 'table' : 'cards';
$sort = (string) ($_GET['sort'] ?? 'name');
$dir = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
$testTarget = trim((string) ($_GET['test'] ?? ''));

function buildUrl(array $params): string
{
    return '?' . http_build_query($params);
}

function testUrl(array $baseParams, string $domain): string
{
    return buildUrl(array_merge($baseParams, ['test' => $domain]));
}

function testAllUrl(array $baseParams): string
{
    return buildUrl(array_merge($baseParams, ['test' => 'all']));
}

function sortLink(string $column, string $label, string $currentSort, string $currentDir, string $view, string $search): string
{
    $direction = 'asc';
    if ($currentSort === $column) {
        $direction = $currentDir === 'asc' ? 'desc' : 'asc';
    }

    $arrow = '';
    if ($currentSort === $column) {
        $arrow = $currentDir === 'asc' ? ' ▲' : ' ▼';
    }

    $url = buildUrl([
        'search' => $search,
        'view' => $view,
        'sort' => $column,
        'dir' => $direction,
    ]);

    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="sort-link">' . htmlspecialchars($label . $arrow, ENT_QUOTES, 'UTF-8') . '</a>';
}

function safeValue(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? '—'), ENT_QUOTES, 'UTF-8');
}

function httpCheck(string $host, ?string $ip): array
{
    if ($ip === null || $ip === '') {
        return [
            'ok' => false,
            'url' => 'http://' . $host,
            'code' => null,
            'content_type' => null,
            'title' => null,
            'html' => null,
            'message' => 'DNS non résolu',
        ];
    }

    $target = str_contains($ip, ':') && !str_starts_with($ip, '[') ? '[' . $ip . ']' : $ip;
    $url = 'http://' . $target . '/';
    $browserUserAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    $ch = curl_init($url);
    if ($ch === false) {
        return [
            'ok' => false,
            'url' => $url,
            'code' => null,
            'content_type' => null,
            'title' => null,
            'html' => null,
            'message' => 'curl_init indisponible',
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => $browserUserAgent,
        CURLOPT_HTTPHEADER => [
            'Host: ' . $host,
        ],
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if (!is_string($body) || $body === '') {
        return [
            'ok' => false,
            'url' => $url,
            'code' => $code ?: null,
            'content_type' => $contentType !== '' ? $contentType : null,
            'title' => null,
            'html' => null,
            'message' => $error !== '' ? $error : 'Pas de réponse HTTP',
        ];
    }

    $isHtml = stripos($contentType, 'html') !== false || preg_match('~<html\b~i', $body) === 1;
    $title = null;
    if (preg_match('~<title[^>]*>(.*?)</title>~is', $body, $matches)) {
        $title = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return [
        'ok' => $isHtml && $code >= 200 && $code < 400,
        'url' => $url,
        'code' => $code ?: null,
        'content_type' => $contentType !== '' ? $contentType : null,
        'title' => $title,
        'html' => $isHtml ? $body : null,
        'message' => $isHtml ? 'HTML reçu' : 'Réponse non HTML',
    ];
}

$filteredDomains = array_values(array_filter($domains, static function (array $domain) use ($search): bool {
    if ($search === '') {
        return true;
    }

    return stripos($domain['name'], $search) !== false || stripos($domain['domain'], $search) !== false;
}));

$testedDomains = array_map(static function (array $domain): array {
    $dns = DnsResolver::resolve($domain['domain']);
    $wwwDns = DnsResolver::resolve('www.' . $domain['domain']);

    return array_merge($domain, [
        'up' => $dns['up'],
        'address' => $dns['address'],
        'www_up' => $wwwDns['up'],
        'www_address' => $wwwDns['address'],
    ]);
}, $filteredDomains);

if ($testTarget === 'all') {
    foreach ($testedDomains as &$domain) {
        $domain['http'] = httpCheck($domain['domain'], $domain['address']);
        $domain['www_http'] = httpCheck('www.' . $domain['domain'], $domain['www_address']);
    }
    unset($domain);
} elseif ($testTarget !== '') {
    foreach ($testedDomains as &$domain) {
        if ($domain['domain'] === $testTarget) {
            $domain['http'] = httpCheck($domain['domain'], $domain['address']);
            $domain['www_http'] = httpCheck('www.' . $domain['domain'], $domain['www_address']);
            break;
        }
    }
    unset($domain);
}

usort($testedDomains, static function (array $left, array $right) use ($sort, $dir): int {
    $fields = [
        'name' => 'name',
        'domain' => 'domain',
        'dns' => 'up',
        'address' => 'address',
        'www' => 'www_up',
        'www_address' => 'www_address',
    ];

    $field = $fields[$sort] ?? 'name';

    if ($field === 'up' || $field === 'www_up') {
        $comparison = ($left[$field] ?? false) <=> ($right[$field] ?? false);
    } elseif ($field === 'http') {
        $comparison = ($left[$field]['ok'] ?? false) <=> ($right[$field]['ok'] ?? false);
    } else {
        $comparison = strcasecmp((string) ($left[$field] ?? ''), (string) ($right[$field] ?? ''));
    }

    return $dir === 'desc' ? -$comparison : $comparison;
});

$onlineCount = count(array_filter($testedDomains, static function (array $domain): bool {
    return (bool) (($domain['http']['ok'] ?? false) || ($domain['www_http']['ok'] ?? false));
}));
$testedCount = count(array_filter($testedDomains, static function (array $domain): bool {
    return isset($domain['http']) || isset($domain['www_http']);
}));
$offlineCount = $testedCount - $onlineCount;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homedns</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <main class="shell">
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">Gestion DNS</p>
                <div class="brand">
                    <img src="siolatin.png" alt="Logo Homedns" class="brand-logo">
                    <h1>Homedns</h1>
                </div>
                <p class="hero-text">Test DNS puis requête HTTP simple sur le port 80 de l’IP résolue. OK si du HTML répond, KO sinon.</p>

                <div class="hero-stats">
                    <div class="stat-card">
                        <span class="stat-value"><?php echo count($testedDomains); ?></span>
                        <span class="stat-label">entrées</span>
                    </div>
                    <div class="stat-card stat-up">
                        <span class="stat-value"><?php echo $onlineCount; ?></span>
                        <span class="stat-label">tests OK</span>
                    </div>
                    <div class="stat-card stat-down">
                        <span class="stat-value"><?php echo $offlineCount; ?></span>
                        <span class="stat-label">tests KO</span>
                    </div>
                </div>
            </div>

            <div class="hero-panel">
                <form action="index.php" method="get" class="search-bar">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="text" id="search" name="search" placeholder="Rechercher un prénom ou domaine" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button button-ghost">Rechercher</button>
                    <a class="button button-primary" href="<?php echo htmlspecialchars(buildUrl(['search' => $search, 'view' => $view, 'sort' => $sort, 'dir' => $dir]), ENT_QUOTES, 'UTF-8'); ?>">Rafraîchir</a>
                    <a class="button button-primary" href="<?php echo htmlspecialchars(testAllUrl(['search' => $search, 'view' => $view, 'sort' => $sort, 'dir' => $dir]), ENT_QUOTES, 'UTF-8'); ?>">Tout tester</a>
                    <a class="button button-ghost" href="<?php echo htmlspecialchars(buildUrl(['search' => $search, 'view' => $view === 'table' ? 'cards' : 'table', 'sort' => $sort, 'dir' => $dir]), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo $view === 'table' ? 'Vue cartes' : 'Vue tableau'; ?>
                    </a>
                </form>
            </div>
        </section>

        <?php if (empty($testedDomains)): ?>
            <section class="empty-state">
                <p>Aucun domaine trouvé.</p>
            </section>
        <?php elseif ($view === 'table'): ?>
            <section class="table-wrap">
                <table class="dns-table">
                    <thead>
                        <tr>
                            <th><?php echo sortLink('name', 'Prénom', $sort, $dir, $view, $search); ?></th>
                            <th><?php echo sortLink('domain', 'Domaine', $sort, $dir, $view, $search); ?></th>
                            <th><?php echo sortLink('dns', 'DNS', $sort, $dir, $view, $search); ?></th>
                            <th><?php echo sortLink('address', 'IP', $sort, $dir, $view, $search); ?></th>
                            <th><?php echo sortLink('www', 'www', $sort, $dir, $view, $search); ?></th>
                            <th><?php echo sortLink('www_address', 'IP www', $sort, $dir, $view, $search); ?></th>
                            <th>Test</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($testedDomains as $domain): ?>
                            <tr>
                                <td><?php echo safeValue($domain['name']); ?></td>
                                <td><code><?php echo safeValue($domain['domain']); ?></code></td>
                                <td><span class="badge <?php echo $domain['up'] ? 'badge-up' : 'badge-down'; ?>"><?php echo $domain['up'] ? 'OK' : 'KO'; ?></span></td>
                                <td><?php echo safeValue($domain['address']); ?></td>
                                <td><span class="badge <?php echo $domain['www_up'] ? 'badge-up' : 'badge-down'; ?>"><?php echo $domain['www_up'] ? 'OK' : 'KO'; ?></span></td>
                                <td><?php echo safeValue($domain['www_address']); ?></td>
                                <td>
                                    <?php if (isset($domain['http'])): ?>
                                        <span class="badge <?php echo $domain['http']['ok'] ? 'badge-up' : 'badge-down'; ?>"><?php echo $domain['http']['ok'] ? 'OK' : 'KO'; ?></span>
                                        <div class="table-preview">
                                            <strong><?php echo safeValue($domain['http']['title'] ?? null); ?></strong>
                                            <p><?php echo safeValue($domain['http']['message'] ?? null); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <a class="button button-ghost button-small" href="<?php echo htmlspecialchars(testUrl(['search' => $search, 'view' => $view, 'sort' => $sort, 'dir' => $dir], $domain['domain']), ENT_QUOTES, 'UTF-8'); ?>">Tester</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php else: ?>
            <section class="grid">
                <?php foreach ($testedDomains as $domain): ?>
                    <?php
                        $httpResult = $domain['http'] ?? null;
                        $wwwHttpResult = $domain['www_http'] ?? null;
                        $cardOk = (bool) (($httpResult['ok'] ?? false) && ($wwwHttpResult['ok'] ?? false));
                    ?>
                    <article class="card <?php echo $cardOk ? 'card-up' : 'card-down'; ?>">
                        <div class="card-top">
                            <div>
                                <h2><?php echo safeValue($domain['name']); ?></h2>
                                <p class="domain-name"><?php echo safeValue($domain['domain']); ?></p>
                            </div>

                            <span class="badge <?php echo $cardOk ? 'badge-up' : 'badge-down'; ?>">
                                <?php echo $cardOk ? 'OK' : 'KO'; ?>
                            </span>
                        </div>

                        <div class="card-meta">
                            <div>
                                <span class="meta-label">Domaine</span>
                                <span class="meta-value meta-line"><span class="status-chip <?php echo $domain['up'] ? 'status-ok' : 'status-ko'; ?>">DNS <?php echo $domain['up'] ? 'OK' : 'KO'; ?></span></span>
                                <span class="meta-value meta-line">IP: <?php echo safeValue($domain['address']); ?></span>
                                <span class="meta-value meta-line">
                                    <span class="status-chip <?php echo ($httpResult['ok'] ?? false) ? 'status-ok' : 'status-ko'; ?>">
                                        Port 80 <?php echo $httpResult ? ($httpResult['ok'] ? 'OK' : 'KO') : '—'; ?>
                                    </span>
                                </span>
                            </div>
                            <div>
                                <span class="meta-label">WWW</span>
                                <span class="meta-value meta-line"><span class="status-chip <?php echo $domain['www_up'] ? 'status-ok' : 'status-ko'; ?>">DNS <?php echo $domain['www_up'] ? 'OK' : 'KO'; ?></span></span>
                                <span class="meta-value meta-line">IP: <?php echo safeValue($domain['www_address']); ?></span>
                                <span class="meta-value meta-line">
                                    <span class="status-chip <?php echo ($wwwHttpResult['ok'] ?? false) ? 'status-ok' : 'status-ko'; ?>">
                                        Port 80 <?php echo $wwwHttpResult ? ($wwwHttpResult['ok'] ? 'OK' : 'KO') : '—'; ?>
                                    </span>
                                </span>
                            </div>
                        </div>

                        <a class="card-link" href="<?php echo htmlspecialchars(testUrl(['search' => $search, 'view' => $view, 'sort' => $sort, 'dir' => $dir], $domain['domain']), ENT_QUOTES, 'UTF-8'); ?>">
                            Tester domaine + www
                        </a>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
