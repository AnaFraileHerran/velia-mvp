#!/bin/zsh -l
# Ejecuta scripts-claude/tarea.sh y guarda la salida en storage/logs/claude/tarea.log (lo lanza Claude)
cd "$(dirname "$0")/.."
LOG=storage/logs/claude/tarea.log
echo "== INICIO $(date)" > $LOG
zsh scripts-claude/tarea.sh >> $LOG 2>&1
echo "== FIN código=$? $(date)" >> $LOG
exit 0
