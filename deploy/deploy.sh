#!/usr/bin/env bash
# =============================================================================
#  Résidence Khadija — (re)déploiement sur hébergement mutualisé cPanel
# -----------------------------------------------------------------------------
#  À lancer depuis le Terminal cPanel (ou SSH), à la RACINE de l'application,
#  APRÈS un « git pull » :
#
#      cd ~/laravel/residence-khadija && bash deploy/deploy.sh
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

# Détecte la bonne commande PHP (cPanel expose parfois « ea-php84 »).
PHP_BIN="${PHP_BIN:-php}"
echo "==> PHP : $($PHP_BIN -v | head -n1)"

echo "==> Mode maintenance"
$PHP_BIN artisan down --render="errors::503" --retry=15 || true
trap '$PHP_BIN artisan up || true' EXIT

echo "==> Dépendances PHP (production, sans dev)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Migrations base de données"
$PHP_BIN artisan migrate --force

echo "==> Lien symbolique storage/app/public -> public/storage"
$PHP_BIN artisan storage:link || true

echo "==> Recompilation des caches (config / routes / vues / events)"
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
