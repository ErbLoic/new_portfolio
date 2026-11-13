# Portfolio BTS SIO — Symfony (tout-en-un)

Remplace les trois projets séparés :

| Avant | Maintenant |
|---|---|
| `Portfolio/` (site HTML/JS) | `/`, `/projet/{id}`, `/realisation/{id}` — même HTML/CSS/JS, données injectées par Twig |
| `portfolio_api/` (Laravel + JWT) | Contrôleurs Symfony + Doctrine, **même base de données** |
| `admin-portfolio/` (SPA JS) | `/admin` — pages Twig, connexion par session |

## Installation

```bash
composer install
```

Créer `.env.local` (jamais commité) :

```dotenv
# Base actuelle du portfolio (celle de l'API Laravel, PostgreSQL)
DATABASE_URL="postgresql://UTILISATEUR:MOT_DE_PASSE@HOTE:5432/BASE?serverVersion=16&charset=utf8"

# Formulaire de contact
MAILER_DSN=smtp://UTILISATEUR:MOT_DE_PASSE@smtp.exemple.com:587
MAILER_FROM=portfolio@votre-domaine.fr

# Cloudflare R2 (mêmes valeurs que l'ancienne API). Vides => fichiers dans public/uploads
CLOUDFLARE_R2_ACCESS_KEY_ID=
CLOUDFLARE_R2_SECRET_ACCESS_KEY=
CLOUDFLARE_R2_BUCKET=
CLOUDFLARE_R2_ENDPOINT=
CLOUDFLARE_R2_PUBLIC_URL=

APP_SECRET=une-longue-valeur-aleatoire
```

Lancer : `symfony serve` puis ouvrir http://127.0.0.1:8000 (admin : `/admin`).

## Déploiement sur Render (Docker)

Le `Dockerfile` construit une image de production (PHP 8.5 + Apache). Aucun secret n'est
dans l'image : tout passe par les variables d'environnement de Render.

1. Pousser le projet sur GitHub (vérifier que `.env.local` n'est **pas** commité).
2. Render → **New → Blueprint** → choisir le dépôt : `render.yaml` crée le service.
   (ou **New → Web Service → Docker**, puis saisir les variables ci-dessous à la main)
3. Renseigner les variables demandées :

| Variable | Valeur |
|---|---|
| `DATABASE_URL` | URL **Session pooler** de Supabase, avec `?serverVersion=15&charset=utf8` |
| `MAILER_DSN` | `gmail+smtp://ADRESSE%40gmail.com:MOT_DE_PASSE_APPLICATION@default` (mot de passe **sans espaces**) |
| `MAILER_FROM` | ton adresse Gmail |
| `CLOUDFLARE_R2_*` | les 5 valeurs R2 (obligatoires : le disque de Render est effacé à chaque déploiement) |
| `SITE_HOST_INFO` | hébergeur pour les mentions légales, ex. `Render Services, Inc.\nAdresse…` |
| `APP_SECRET` | généré automatiquement par le blueprint |

Ensuite :
- Healthcheck : `/api/ping` (aussi utilisable par un service de ping pour éviter la mise en veille du plan gratuit).
- Le premier démarrage prépare le cache Symfony (~10 s).
- Domaine personnalisé : Render → Settings → Custom Domains.

Tester l'image en local :

```bash
docker build -t portfolio .
docker run --rm -p 8080:10000 -e APP_SECRET=un-secret-local -e DATABASE_URL="postgresql://…" portfolio
```

## Compte admin

Les comptes `admin` de l'ancienne API fonctionnent tels quels (mots de passe bcrypt compatibles).
Pour créer un admin ou changer un mot de passe :

```bash
php bin/console app:admin vous@exemple.fr
```

## ⚠️ Base de données

Les entités Doctrine sont calquées sur les tables **existantes** créées par Laravel.
**Ne lancez pas** `doctrine:schema:update` ni `doctrine:migrations:diff` sur la base de production :
Doctrine voudrait modifier les tables de liaison (`competence_project`, `competence_stage`) et les
tables propres à Laravel. Aucune migration n'est nécessaire.

## Développement local (base jetable)

```bash
docker run -d --name portfolio-pg-test -e POSTGRES_USER=app -e POSTGRES_PASSWORD=app_local_test -e POSTGRES_DB=portfolio -p 55432:5432 postgres:16-alpine
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load   # données fictives, admin@example.test / admin-local-test-1234
```

## Ajouter une section à l'admin

1. Créer l'entité dans `src/Entity/` et son formulaire dans `src/Form/`.
2. Ajouter une entrée dans `src/Admin/AdminSections.php` (libellé, icône, colonnes, fichiers, galerie)
   et le slug dans le `requirements` de `src/Controller/Admin/CrudController.php`.

Liste, création, modification, suppression, envoi de fichiers et galerie sont alors automatiques.

## Différences avec l'ancienne version

- Plus d'API JSON publique : le site lit les données rendues par le serveur (chargement instantané,
  plus de serveur à « réveiller »).
- Connexion admin par session (avec « Rester connecté 7 jours ») au lieu de JWT ; l'inscription
  publique de comptes « lecteur » (`/api/auth/register`) n'est pas reprise.
- Ajout dans l'admin des **projets perso** (gérés par l'API mais absents de l'ancien admin).
