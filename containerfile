FROM php:8.3-apache

# Extensions PHP nécessaires :
# - pdo_mysql   : connexion à la base (PDO utilisé partout dans le projet)
# - mbstring    : mb_strlen() utilisé dans les validations de formulaires
# - gd          : ré-encodage des images uploadées (traiter_upload_photo)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libwebp-dev libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# mod_rewrite est indispensable pour le .htaccess du projet (routage via index.php?url=...)
RUN a2enmod rewrite

# Vhost custom : DocumentRoot pointé sur le sous-dossier du projet, et
# AllowOverride All pour que le .htaccess soit pris en compte (l'image
# officielle php:apache le désactive par défaut).
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html