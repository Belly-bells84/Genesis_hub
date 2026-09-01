# Déploiement Docker

## Arborescence à respecter (racine du projet)

```
votre-projet/
├── docker-compose.yml          ← à la racine
├── .env                        ← créé à partir de .env.example, à la racine
├── .dockerignore                ← à la racine
├── docker/
│   ├── Dockerfile
│   ├── apache-config/
│   │   └──000-default.conf
│   ├── php-config/
│   │   └── uploads.ini
│   └── mysql-init/
│       ├── 01-schema.sql       ← VOTRE schéma existant (voir plus bas)
│       └── 02-securite.sql     ← la migration donnée précédemment (déjà placée)
├── .htaccess                    (racine du site, déjà en place)
├── index.php
├── config/
├── models/
├── asset/IMG/uploads/.htaccess (déjà en place)
└── traiter_*.php
```

## ⚠️ Étape à faire vous-même : le schéma de base

Le fichier `docker/mysql-init/02-securite.sql` que je vous ai donné fait un
`ALTER TABLE account_user ADD CONSTRAINT ...` — il suppose que la table
`account_user` existe déjà. Or un conteneur MySQL neuf part d'une base
vide.

Tous les fichiers `.sql` posés dans `docker/mysql-init/` sont exécutés
automatiquement, dans l'ordre alphabétique, **au tout premier démarrage
du conteneur MySQL uniquement** (si le volume `db_data` est vide).

**Donc** : exportez votre schéma actuel (`mysqldump --no-data` ou un
export complet depuis phpMyAdmin) et déposez-le dans
`docker/mysql-init/01-schema.sql`, pour qu'il s'exécute avant
`02-securite.sql`. Si vous voulez, envoyez-moi ce dump et je vérifie
que l'ordre et la contrainte `UNIQUE` s'appliquent proprement dessus.

## Système de variables d'environnement (spécifique à ce projet, à bien comprendre avant de démarrer)

Votre `config/connexion.php` dépend de `config/env_loader.php`, qui
fonctionne en deux temps :

1. Il lit `.env` (racine) pour connaître `APP_ENV` (`dev` ou `prod`).
2. Il charge ensuite `.env.dev` ou `.env.prod` selon le cas, qui contient
   les vraies valeurs (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

Ces valeurs sont chargées via `putenv()` à chaque requête, directement
depuis les fichiers montés dans le conteneur par le volume `./:/var/www/html`.
Elles **priment toujours** sur ce que Docker Compose aurait pu injecter
par ailleurs — c'est pourquoi le service `app` du `docker-compose.yml` ne
définit volontairement aucune variable `DB_*` : ça n'aurait aucun effet
réel et prêterait à confusion.

**Trois fichiers à créer à la racine, aucun ne doit être commité dans Git :**

| Fichier | Rôle | Modèle fourni |
|---|---|---|
| `.env` | `APP_ENV=dev` pour l'app, + sert aussi à Docker Compose pour configurer le conteneur MySQL lui-même (`${DB_NAME}` etc. dans `docker-compose.yml`) | `.env.example` |
| `.env.dev` | Vraies valeurs lues par `env_loader.php` en développement | `.env.dev.example` |
| `.env.prod` | Idem pour un déploiement en production (hors de ce Docker de dev) | à créer vous-même selon votre hébergement réel |

⚠️ `DB_NAME` / `DB_USER` / `DB_PASS` doivent être **identiques** dans
`.env` et `.env.dev` : les deux décrivent la même base du conteneur `db`,
juste pour deux systèmes différents (Compose d'un côté, `env_loader.php`
de l'autre).

```bash
cp .env.example .env
cp .env.dev.example .env.dev
# éditez les deux fichiers avec les mêmes DB_NAME / DB_USER / DB_PASS

docker compose up -d --build
```

L'application sera sur **http://localhost:8080**, phpMyAdmin sur
**http://localhost:8081** (pratique en dev, à retirer du
`docker-compose.yml` avant un vrai déploiement en production).

## Test de la protection .htaccess dans ce Docker

```bash
docker compose exec app bash -c "echo '<?php echo \"test\";' > /var/www/html/asset/IMG/uploads/test.php"
curl -i http://localhost:8080/asset/IMG/uploads/test.php
# Doit renvoyer 403 Forbidden, pas "test"
docker compose exec app rm /var/www/html/asset/IMG/uploads/test.php
```

Si vous obtenez "test" au lieu d'un 403, c'est que `AllowOverride All`
n'est pas pris en compte — vérifiez que `docker/apache-config/000-default.conf`
est bien monté (`docker compose exec app cat /etc/apache2/sites-available/000-default.conf`).