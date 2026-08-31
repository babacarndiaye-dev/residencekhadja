#!/usr/bin/env bash
# =============================================================================
#  Résidence Khadija — (re)déploiement sur hébergement mutualisé cPanel
# -----------------------------------------------------------------------------
#  À lancer depuis le Terminal cPanel (ou SSH), à la RACINE de l'application,
#  APRÈS un « git pull » :
#
#      cd /home/USER/laravel/residence-khadija && bash deploy/deploy.sh
#
#  L'app EXIGE PHP >= 8.4 (composants Symfony 8). Le script cherche un binaire
#  8.4 tout seul ; sinon, forcer :  PHP_BIN=/opt/cpanel/ea-php84/root/usr/bin/php bash deploy/deploy.sh
#
#  Les assets front (public/build) sont versionnés dans le dépôt : pas de
#  « npm run build » ici, l'hébergement mutualisé n'a pas Node.
# =============================================================================
set -euo pipefail

cd "$(dirname "$0")/.."
echo "==> Application : $(pwd)"

if [ ! -f .env ]; then
    echo "!! Aucun fichier .env — copier .env.production.example vers .env et le remplir d'abord." >&2
    exit 1
fi

# --- Choix du binaire PHP : impérativement >= 8.4 -----------------------------
if [ -z "${PHP_BIN:-}" ]; then
    for c in php84 ea-php84 \
             /opt/cpanel/ea-php84/root/usr/bin/php \
             /opt/alt/php84/usr/bin/php \
             /usr/local/bin/ea-php84 \
             php; do
        if command -v "$c" >/dev/null 2>&1; then PHP_BIN="$c"; break; fi
    done
fi
PHP_BIN="${PHP_BIN:-php}"
echo "==> PHP : $($PHP_BIN -v | head -n1)"
if ! $PHP_BIN -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);'; then
    echo "!! $PHP_BIN est trop ancien (PHP 8.4 requis). Relancer avec :" >&2
    echo "     PHP_BIN=/opt/cpanel/ea-php84/root/usr/bin/php bash deploy/deploy.sh" >&2
    exit 1
fi

# --- Composer sous le MÊME PHP (ne rien faire si vendor/ déjà fourni) --------
COMPOSER_BIN="$(command -v composer 2>/dev/null || command -v composer.phar 2>/dev/null || echo /opt/cpanel/composer/bin/composer)"

# APP_KEY manquante => 500 « No application encryption key » sur chaque page.
if ! grep -qE '^APP_KEY=base64:.+' .env; then
    echo "==> APP_KEY absente — génération"
    $PHP_BIN artisan key:generate --force
fi

echo "==> Mode maintenance"
$PHP_BIN artisan down --render="errors::503" --retry=15 || true
trap '$PHP_BIN artisan up || true' EXIT

if [ -x "$COMPOSER_BIN" ] || command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    echo "==> Dépendances PHP (production, sans dev)"
    $PHP_BIN "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction --prefer-dist
else
    echo "==> Composer introuvable — on suppose vendor/ déjà en place (upload manuel)"
    [ -f vendor/autoload.php ] || { echo "!! vendor/autoload.php manquant et pas de Composer." >&2; exit 1; }
fi

echo "==> Migrations base de données"
$PHP_BIN artisan migrate --force

echo "==> Lien symbolique storage/app/public -> public/storage"
$PHP_BIN artisan storage:link || true

echo "==> Purge puis recompilation des caches"
# optimize:clear D'ABORD : un config.php en cache avec un .env périmé (ex. sqlite)
# provoque un 500 tenace même après correction du .env.
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache || true

echo "==> Redémarrage des workers de file"
$PHP_BIN artisan queue:restart || true

echo "==> Sortie du mode maintenance"
$PHP_BIN artisan up
trap - EXIT

echo "==> Déploiement terminé."
