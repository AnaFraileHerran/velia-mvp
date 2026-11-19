# Velia · MVP

Wireframes funcionales del MVP de Velia montados con Laravel y [Filament](https://filamentphp.com). Siguen la página «Wireframes v2 · propuesta UX» de Figma y los flujos del FigJam.

## Arrancar en local

```bash
git clone https://github.com/AnaFraileHerran/velia-mvp
cd velia-mvp
bash bin/setup.sh
php artisan serve
```

- **Dirección** (web): http://localhost:8000/direccion · `marta@losalmendros.es` / `velia2026`
- **Turno** (auxiliar, tablet o móvil): http://localhost:8000/turno · PIN de Lucía `1234` · PIN de Ana, responsable de turno, `2468` · Inés entra por primera vez con el PIN provisional `4827`

Los datos son ficticios (Residencia Los Almendros).

## Cambiar una pantalla con Claude Code

1. Abre el repo con Claude Code. Lee `CLAUDE.md` (reglas de producto) y `docs/mapa-pantallas.md` (qué archivo es cada pantalla).
2. Pide el cambio en lenguaje normal, por ejemplo: «En el relevo de la auxiliar, pon las preguntas de la dirección debajo de las novedades».
3. Revisa la pantalla en el navegador a 390 px (móvil) y a tablet horizontal.
4. Si el cambio altera un flujo, actualiza el FigJam y la página de wireframes de Figma.

## Qué es simulación

- **Voz**: usa el reconocimiento de voz del navegador (Chrome) si está disponible; si no, se escribe.
- **«La IA ordena el registro»**: es una clasificación por palabras clave (`app/Support/OrdenarRegistro.php`) para poder probar el flujo. No hay modelo de IA conectado.
- **Envío a familias**: no se envía nada; el resumen pasa a «Enviado».
