#!/usr/bin/env bash
# Instala Velia en local: crea el esqueleto de Laravel alrededor del código de este repo,
# añade Filament, registra los paneles y carga los datos ficticios.
# Requisitos: PHP 8.2+, Composer, Node no es necesario para Filament.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f artisan ]; then
  echo "→ Creando el esqueleto de Laravel 11…"
  composer create-project laravel/laravel:^11.0 .skeleton --no-interaction
  # Copia solo lo que no existe en el repo (no pisa el código de Velia)
  cp -rn .skeleton/. .
  rm -rf .skeleton
fi

echo "→ Instalando Filament 3…"
composer require filament/filament:"^3.2" -W --no-interaction

echo "→ Registrando los paneles de Velia…"
php -r '
$f = "bootstrap/providers.php";
$c = file_get_contents($f);
foreach (["App\\Providers\\Filament\\DireccionPanelProvider::class", "App\\Providers\\Filament\\TurnoPanelProvider::class"] as $p) {
  if (strpos($c, $p) === false) { $c = str_replace("];", "    ".$p.",\n];", $c); }
}
file_put_contents($f, $c);
'

[ -f .env ] || cp .env.example .env
php artisan key:generate --force
sed -i.bak 's/^APP_NAME=.*/APP_NAME=Velia/; s/^APP_LOCALE=.*/APP_LOCALE=es/; s/^APP_FALLBACK_LOCALE=.*/APP_FALLBACK_LOCALE=es/' .env && rm -f .env.bak
touch database/database.sqlite
php artisan migrate:fresh --seed --force
php artisan filament:assets

echo ""
echo "Listo. Arranca con: php artisan serve"
echo "  Dirección: http://localhost:8000/direccion  ·  marta@losalmendros.es / velia2026"
echo "  Turno (tablet o móvil): http://localhost:8000/turno  ·  PIN de Lucía: 1234 · PIN de Ana (responsable): 2468"
