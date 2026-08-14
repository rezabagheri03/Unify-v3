#!/usr/bin/env bash
# Re-create the local backend test runner after a sandbox snapshot restore.
# System packages (apt) do NOT persist across snapshots; vendor/ also excluded
# to keep the workspace under the 128MB/10k-file budget. This script restores
# everything in ~3-4 minutes. Usage: bash .tools/php-setup.sh
set -e
export DEBIAN_FRONTEND=noninteractive
if ! command -v php >/dev/null; then
  sudo apt-get update -qq
  sudo apt-get install -y -qq php-cli php-sqlite3 php-mbstring php-xml php-zip \
    php-gd php-intl php-curl php-mysql php-bcmath unzip
fi
if ! command -v composer >/dev/null; then
  cd /tmp && (curl -sS https://getcomposer.org/installer -o c.php || wget -q https://getcomposer.org/installer -O c.php)
  php c.php --quiet --install-dir=/usr/local/bin --filename=composer && rm c.php
fi
cd /home/user/unify-repo/unify-backend
COMPOSER_NO_INTERACTION=1 COMPOSER_NO_AUDIT=1 composer install --no-interaction --prefer-dist --no-progress
[ -f .env ] || cp .env.example .env
php artisan key:generate --force -q
php artisan config:clear -q
# IMPORTANT: clean up after testing to stay under the workspace budget:
#   rm -rf vendor .phpunit.cache storage/framework/testing storage/logs/*.log
php artisan --version && echo SETUP_OK
