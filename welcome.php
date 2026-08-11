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
    <link rel="./style.css" rel="stylesheet">
  </head>
  <body>
    <header>
            <a href="#" id="logo">
            <img src="asset/IMG/Logo_GENESIS.png" alt="Logo du réseau social GENESIS"></a>
        <div id="genesis">
            <a href="#">
            <span id="genesis_name">Nom de code : GENESIS</span>
            <span class="genesis_devise">Les coeurs s'envolent,</span>
            <span class="genesis_devise">Genesis les rassemblent</span>
            </a>
        </div>
    <nav class="navigation_genesis">
        <a href="#">S'inscrire</a>
        <a href="#">Se connecter</a>
    </nav>
    <div id="search" class="navigation_genesis">
        <a href="#"><img src="#" alt="Icone de loupe de recherche"></a>
    </div> 
    </header>
    <main>
<!--Section 1 : Lettre ouverte de la fondatrice-->
        <section id="section_1">
            <article id="presentation_fondatrice">
                <img src="asset/IMG/Belen_LEGER_fondatrice.jpg" alt="Photographie de présentation de la créatrice - Belén LEGER">
                    <div class="contenu">
                        <h1>Derriére GENESIS : Le mot de Belén</h1>
                        <p><em>Chére Base Arriére,</em></p>
                        <p>Partager la vie d’un militaire est un engagement invisible, mais total. Partager celle d’un légionnaire, c’est accepter d'entrer dans un monde aux codes uniques, fait d'une immense fierté, mais aussi de très longues attentes et de silences imposés.</p>
                    </div>
                <a href="#">Je veux découvrir la suite de la lettre ouverte !</a>
            </article>
<!--Les valeurs-->
            <div id="contenu_valeur">
                <h2>Ici, nous sommes transparent. Je vous présente nos valeurs !</h2>
                <div>
                    <a href="#">
                        <img src="asset/IMG/Design_valeur_1.png" alt="Première valeur : Liberté">
                    </a>
                </div>
                <div>
                    <a href="#">
                        <img src="asset/IMG/Design_valeur_2.png" alt="Deuxiéme valeur : l'Audace">
                    </a>
                </div>
                <div>
                    <a href="#">
                        <img src="asset/IMG/Design_valeur_3.png" alt="Troisiéme valeur : la Loyauté">
                    </a>
                </div>
            </div>
        </section>
<!--Section 2: new article => new update du site ou application-->
        <section id="section_2">
            <article>
                <h2></h2>
                <p></p>
            </article>
<!--New article : Derniers articles publiés pour les utilisateurs-->
            <div>
                <div>
                    <a href="#">
                        <img src="#" alt="Description de l'image de l'article">
                        <h3>Titre de l'article_1</h3>
                    </a>
                </div>

                <div>
                    <a href="#">
                        <img src="#" alt="Description de l'image de l'article">
                        <h3>Titre de l'article_2</h3>
                    </a>
                </div>

                <div>
                    <a href="#">
                        <img src="#" alt="Description de l'image de l'article">
                        <h3>Titre de l'article_3</h3>
                    </a>
                </div>
            </div>
        </section>
    </main>
<?php include '/footer.php';