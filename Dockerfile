# ─────────────────────────────────────────────────────────────────────────────
#  Portfolio Symfony — image de production (Render, ou tout hébergeur Docker)
#
#  Aucun secret dans l'image : toutes les valeurs sensibles (DATABASE_URL,
#  MAILER_DSN, APP_SECRET, clés R2…) sont fournies en variables d'environnement
#  au démarrage par l'hébergeur.
# ─────────────────────────────────────────────────────────────────────────────

# ---- Étape 1 : dépendances PHP (Composer) ------------------------------------
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress --ignore-platform-req=ext-*
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction

# ---- Étape 2 : image finale (PHP 8.5 + Apache) -------------------------------
FROM php:8.5-apache

# Extensions : PostgreSQL (Supabase), intl ; certificats SSL pour R2 / Gmail
# (opcache est intégré d'office depuis PHP 8.5)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libicu-dev ca-certificates \
 && docker-php-ext-install pdo_pgsql intl \
 && rm -rf /var/lib/apt/lists/*

# Réglages PHP de production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'realpath_cache_size=4096K'; \
      echo 'realpath_cache_ttl=600'; \
      echo 'upload_max_filesize=6M'; \
      echo 'post_max_size=40M'; \
      echo 'expose_php=Off'; \
      echo '; Les variables de Render (APP_ENV, DATABASE_URL…) doivent primer sur le fichier .env'; \
      echo 'variables_order=EGPCS'; \
    } > "$PHP_INI_DIR/conf.d/zz-portfolio.ini"

# Apache : racine sur public/, toutes les routes vers index.php, en-têtes de sécurité
RUN a2enmod rewrite headers \
 && { \
      echo '<VirtualHost *:${PORT}>'; \
      echo '    DocumentRoot /var/www/html/public'; \
      echo '    <Directory /var/www/html/public>'; \
      echo '        AllowOverride None'; \
      echo '        Require all granted'; \
      echo '        FallbackResource /index.php'; \
      echo '    </Directory>'; \
      echo '    Header always set X-Content-Type-Options "nosniff"'; \
      echo '    Header always set Referrer-Policy "strict-origin-when-cross-origin"'; \
      echo '    Header always set X-Frame-Options "SAMEORIGIN"'; \
      echo '    ErrorLog /dev/stderr'; \
      echo '    CustomLog /dev/stdout combined'; \
      echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/000-default.conf \
 && echo 'Listen ${PORT}' > /etc/apache2/ports.conf \
 && printf 'ServerTokens Prod\nServerSignature Off\nServerName localhost\n' > /etc/apache2/conf-available/zz-portfolio.conf \
 && a2enconf zz-portfolio

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    PORT=10000 \
    TRUSTED_PROXIES=REMOTE_ADDR

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/portfolio-entrypoint
# (sed : retire d'éventuels retours chariot Windows qui empêcheraient le script de démarrer)
RUN sed -i 's/\r$//' /usr/local/bin/portfolio-entrypoint \
 && chmod +x /usr/local/bin/portfolio-entrypoint \
 && mkdir -p var public/uploads \
 && chown -R www-data:www-data var public/uploads

EXPOSE 10000
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD php -r 'exit(@file_get_contents("http://127.0.0.1:" . getenv("PORT") . "/api/ping") ? 0 : 1);'

ENTRYPOINT ["portfolio-entrypoint"]
CMD ["apache2-foreground"]
