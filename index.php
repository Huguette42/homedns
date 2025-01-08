<?php

$domains = [
    ['name' => 'Alex Artan', 'domain' => 'artan.labo'],
    ['name' => 'Virgile Becas', 'domain' => 'becas.labo'],
    ['name' => 'Mael Belliard', 'domain' => 'belliard.labo'],
    ['name' => 'Clara Escaravage', 'domain' => 'escaravage.labo'],
    ['name' => 'Mosiah Estarque', 'domain' => 'estarque.local'],
    ['name' => 'Clement Guerault', 'domain' => 'guerault.labo'],
    ['name' => 'Ethan Gorini', 'domain' => 'gorini.labo'],
    ['name' => 'Noam Guez', 'domain' => 'guez.labo'],
    ['name' => 'Quentin Jawhar', 'domain' => 'jawhar.labo'],
    ['name' => 'Hugo Jeanselme', 'domain' => 'jeanselme.labo'],
    ['name' => 'Hamza Kilinc', 'domain' => 'kilinc.local'],
    ['name' => 'Elina Mathey', 'domain' => 'mathey.labo'],
    ['name' => 'Thibault Merland', 'domain' => 'merland.labo'],
    ['name' => 'Johan Montagnie', 'domain' => 'Maximumpulse.com'],
    ['name' => 'Hugo Muller', 'domain' => 'muller.labo'],
    ['name' => 'Rafael Olivieri', 'domain' => 'olivieri.local'],
    ['name' => 'Lousi Paret', 'domain' => 'paret.labo'],
    ['name' => 'Loris Petitjean', 'domain' => 'petitjean.local'],
    ['name' => 'Matthieu Poncet', 'domain' => 'poncet.local'],
    ['name' => 'Tom Quay', 'domain' => 'quay.local'],
    ['name' => 'Adil Rampon', 'domain' => 'rampon.labo'],
    ['name' => 'Maxime redier', 'domain' => 'redier.labo'],
    ['name' => 'Arthur Richert', 'domain' => 'richertvillain.local'],
    ['name' => 'Benjamin Soares', 'domain' => 'soares.local'],
    ['name' => 'Alexis Renaze', 'domain' => 'renaze.local'],
    ['name' => 'Selim Usenmez', 'domain' => 'usenmez.labo'],
    ['name' => 'Symeon Vachot', 'domain' => 'vachot.local'],
    ['name' => 'Emma Zhao', 'domain' => 'zhao.local']
];


if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $domains = array_filter($domains, function ($domain) use ($search) {
        return strpos($domain['name'], $search) !== false;
    });
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Dashboard</title>
</head>
<body>
    <h1 class="centered">SIOlaTin <a href="/"><img height="120px" src="siolatin.png"/></a></h1>
    <p>Voici nos differentes boulangerie du groupe SIOlaTin</p>
    <br>
    <div class="test">
        <form action="index.php" method="get">
            <input type="text" id="search" name="search" placeholder="Rechercher une boulangerie">
            <button type="submit" id="searchButton">Rechercher</button>
        </form>
    </div>
    <div id="boulangeries">
        <?php foreach ($domains as $domain): ?>
            <div class="boulangerie">
                <h2><?= $domain['name'] ?></h2>
                <a href="http://<?= $domain['domain'] ?>"><p><?= $domain['domain'] ?></p></a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>