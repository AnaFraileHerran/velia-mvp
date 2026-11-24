#!/usr/bin/env bash
# Instala Velia en local después de clonar el repositorio (GitLab o GitHub).
# Requisitos: PHP 8.3+ con las extensiones intl, zip y pdo_sqlite, y Composer.
# Node 20+ es opcional: sin él, Velia funciona con el aspecto base de Filament.
set -euo pipefail
cd "$(dirname "$0")/.."

echo "→ Instalando dependencias de PHP (Laravel 13, Filament 5)…"
composer install --no-interaction

echo "→ Estilos de Velia (tema de Filament)…"
if command -v npm >/dev/null 2>&1; then
  npm ci --silent && npm run build || echo "⚠︎ No se pudo compilar el tema: Velia funcionará con el aspecto base de Filament."
else
  echo "⚠︎ No hay Node/npm: Velia funcionará con el aspecto base de Filament. Instala Node 20+ (nodejs.org) y vuelve a lanzar el script."
fi

[ -f .env ] || cp .env.example .env
php artisan key:generate --force
sed -i.bak 's/^APP_NAME=.*/APP_NAME=Velia/; s/^APP_LOCALE=.*/APP_LOCALE=es/; s/^APP_FALLBACK_LOCALE=.*/APP_FALLBACK_LOCALE=es/' .env && rm -f .env.bak
touch database/database.sqlite
php artisan migrate:fresh --seed --force
php artisan storage:link --force >/dev/null 2>&1 || true   # fotos de residentes, equipo y residencia
php artisan filament:assets

echo ""
echo "Listo. Arranca con: php artisan serve   (o php artisan serve --port=8001 si el 8000 está ocupado)"
echo "  Dirección: http://localhost:8000/direccion  ·  marta@losalmendros.es / velia2026"
echo "  Turno (tablet o móvil): http://localhost:8000/turno  ·  PIN de Lucía: 1234 · PIN de Ana (responsable): 2468"
