#!/bin/sh
# Démarrage du conteneur : vérifie la config puis prépare le cache Symfony
# avec les variables d'environnement fournies par l'hébergeur.
set -e

missing=""
for var in APP_SECRET DATABASE_URL; do
  eval "val=\${$var:-}"
  [ -z "$val" ] && missing="$missing $var"
done
if [ -n "$missing" ]; then
  echo "ERREUR : variables d'environnement manquantes :$missing" >&2
  exit 1
fi

# Cache compilé au démarrage (les variables d'env n'existent pas pendant le build)
php bin/console cache:clear --no-warmup --env=prod
php bin/console cache:warmup --env=prod
chown -R www-data:www-data var

exec "$@"
