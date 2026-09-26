#!/bin/sh

echo "Ejecutando migraciones..."

php artisan migrate --force

echo "Ejecutando seeders..."

php artisan db:seed --force

echo "Iniciando aplicación..."

exec "$@"