<?php
declare(strict_types=1);

$domains = [
    ['name' => 'Boris Perier', 'domain' => 'perier.local'],
   ['name' => 'Loris Petitjean', 'domain' => 'petitjean.local'],
   ['name' => 'Boris Junique', 'domain' => 'junique.local'],
   ['name' => 'Nicolas Dumont', 'domain' => 'dumont.local'],
];

function testDns(string $domain): array
{
    $result = [
        'up' => false,
        'address' => null,
    ];

    $records = @dns_get_record($domain, DNS_A | DNS_AAAA);
    if (is_array($records) && $records !== []) {
        $result['up'] = true;
        foreach ($records as $record) {
            if (!empty($record['ip'])) {
                $result['address'] = $record['ip'];
                break;
            }
            if (!empty($record['ipv6'])) {
                $result['address'] = $record['ipv6'];
                break;
            }
        }

        return $result;
    }

    $host = @gethostbyname($domain);
    if ($host !== $domain) {
        $result['up'] = true;
        $result['address'] = $host;
    }

    return $result;
}

$search = trim((string) ($_GET['search'] ?? ''));

$filteredDomains = array_values(array_filter($domains, static function (array $domain) use ($search): bool {
    if ($search === '') {
        return true;
    }

    return stripos($domain['name'], $search) !== false || stripos($domain['domain'], $search) !== false;
}));

$testedDomains = array_map(static function (array $domain): array {
    $dns = testDns($domain['domain']);

    return array_merge($domain, $dns);
}, $filteredDomains);

$onlineCount = count(array_filter($testedDomains, static fn (array $domain): bool => $domain['up']));
$offlineCount = count($testedDomains) - $onlineCount;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Homedns</title>
</head>
<body>
    <main class="shell">
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">Gestion DNS</p>
                <h1>Homedns</h1>
                <p class="hero-text">Liste fixe de personnes, test DNS automatique à chaque chargement et interface plus moderne.</p>

                <div class="hero-stats">
                    <div class="stat-card">
                        <span class="stat-value"><?= count($testedDomains) ?></span>
                        <span class="stat-label">entrées</span>
                    </div>
                    <div class="stat-card stat-up">
                        <span class="stat-value"><?= $onlineCount ?></span>
                        <span class="stat-label">résolues</span>
                    </div>
                    <div class="stat-card stat-down">
                        <span class="stat-value"><?= $offlineCount ?></span>
                        <span class="stat-label">en erreur</span>
                    </div>
                </div>
            </div>

            <div class="hero-panel">
                <form action="index.php" method="get" class="search-bar">
                    <input type="text" id="search" name="search" placeholder="Rechercher un prénom ou un domaine" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="button button-ghost">Rechercher</button>
                    <a class="button button-primary" href="index.php<?= $search !== '' ? '?search=' . urlencode($search) : '' ?>">Rafraîchir les tests</a>
                </form>
                <p class="hint">Les tests DNS sont faits côté serveur avec <span>dns_get_record()</span> puis <span>gethostbyname()</span>.</p>
            </div>
        </section>

        <section class="grid">
            <?php foreach ($testedDomains as $domain): ?>
                <article class="card <?= $domain['up'] ? 'card-up' : 'card-down' ?>">
                    <div class="card-top">
                        <div>
                            <h2><?= htmlspecialchars($domain['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="domain-name"><?= htmlspecialchars($domain['domain'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <span class="badge <?= $domain['up'] ? 'badge-up' : 'badge-down' ?>">
                            <?= $domain['up'] ? 'OK' : 'KO' ?>
                        </span>
                    </div>

                    <div class="card-meta">
                        <div>
                            <span class="meta-label">Résultat DNS</span>
                            <span class="meta-value"><?= $domain['up'] ? 'Résolu' : 'Introuvable' ?></span>
                        </div>
                        <div>
                            <span class="meta-label">Adresse</span>
                            <span class="meta-value"><?= $domain['address'] !== null ? htmlspecialchars((string) $domain['address'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
                        </div>
                    </div>

                    <a class="card-link" href="http://<?= htmlspecialchars($domain['domain'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                        Ouvrir le domaine
                    </a>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>