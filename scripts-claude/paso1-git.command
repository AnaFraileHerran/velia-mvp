#!/bin/zsh -l
# Paso 1 · Diagnóstico del Mac y guardar el trabajo en git (lo lanza Claude)
cd "$(dirname "$0")/.."
LOG=storage/logs/claude/paso1.log
{
echo "== $(date)"
echo "php: $(which php)"; php -v | head -1
echo "composer: $(which composer)"; composer -V 2>&1 | head -1
echo "node: $(node -v 2>&1) npm: $(npm -v 2>&1)"
php -m | grep -iE "intl|sqlite|zip|mbstring" | tr '\n' ' '; echo
git add -A && git commit -q -m "Trabajo del 23/11/2026: decisiones UX (avisos, turno sin abrir, ficha del equipo, demo) antes de migrar a Filament 5

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_012ECctu4WnAzNVhUUR99oiA" && echo COMMIT_OK
git checkout -b migracion-filament-5 2>&1
git branch --show-current
git log --oneline -2
echo "== FIN"
} > $LOG 2>&1
echo "Listo. Puedes cerrar esta ventana."
