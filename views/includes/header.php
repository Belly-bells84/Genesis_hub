<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GENESIS - Le réseaux social des bases arriéres</title>
<!--Police POPPINS-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<!--Police Betania Patmos-->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Betania+Patmos&display=swap" rel="stylesheet">
<!--CSS-->
    <link rel="stylesheet" href="views/css/style.css"/>
<!--Sentry JS--->
    <script src="https://js-de.sentry-cdn.com/11c97357c696b3c26ef5c86b25d1c547.min.js" crossorigin="anonymous"></script>
<!--JS-->
    <script src="/views/js/global.js" defer></script>
  </head>
  <body class="<?= $_SESSION['color'] ?? 'neutral' ?>">
    <?php $lien_accueil = utilisateur_connecte() ? '/feed' : '/'; ?>
    <header>
            <a href="<?= $lien_accueil ?>" id="logo">
            <img src="/views/asset/IMG/Genesis.png" alt="Logo du réseau social GENESIS"></a>
        <div id="genesis">
            <a href="<?= $lien_accueil ?>">
            <span id="genesis_name">The GENESIS</span>
            <span class="genesis_devise">Les coeurs s'envolent,</span>
            <span class="genesis_devise">Genesis les rassemblent</span>
            </a>
        </div>
    <nav class="navigation_genesis">
        <?php if (utilisateur_connecte()): ?>
            <a href="/profil">Mon profil</a>
            <a href="/messages">Messages</a>
            <a href="/deconnexion">Déconnexion</a>
        <?php else: ?>
            <a href="/inscription">S'inscrire</a>
            <a href="/connexion">Se connecter</a>
        <?php endif; ?>
    </nav>
    </header>
    <main>