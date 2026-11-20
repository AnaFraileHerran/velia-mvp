# Velia · MVP (Laravel + Filament)

Software para residencias de mayores de Madrid con menos de 50 plazas. Producto de Icarus.
Este repositorio es la fuente de los wireframes funcionales: cada pantalla de «Wireframes v2 · propuesta UX» (Figma) es un archivo de este proyecto. Cuando el equipo pida un cambio de pantalla, se cambia aquí.

## Referencias
- Wireframes v2 (Figma, página «Wireframes v2 · propuesta UX»): https://www.figma.com/design/jX0tVZ5A0uheE4VzqXw62X
- Flujos y MoSCoW (FigJam): https://www.figma.com/board/pUdiiOvLcHCzL0mtFtyhps
- Prototipo anterior (referencia de contenido): https://frabjous-selkie-afb6cd.netlify.app/
- Estilos: Filament v3 (https://filamentphp.com). Usa sus componentes y su tema; no inventes CSS si Filament ya lo resuelve.

## Stack
- Laravel 12, PHP 8.3, Filament 3.3, Livewire 3, SQLite en local.
- Dos paneles de Filament:
  - `direccion` en `/direccion`: web de la dirección, entra con email y contraseña.
  - `turno` en `/turno`: app de las auxiliares para tablet compartida o móvil, entra con PIN.

## Reglas de producto (no romperlas sin decisión explícita)
1. Nombre del producto: **Velia**. Centro de ejemplo: **Residencia Los Almendros**. Nunca marcadores tipo `[NOMBRE DEL PRODUCTO]`.
2. La auxiliar usa **tablet o móvil**. Toda pantalla del panel `turno` debe funcionar a 390 px de ancho y en tablet horizontal (1194 × 834).
3. Nada bloquea un registro: si falla el micrófono o la conexión, siempre hay «Escribir»; si se olvida el PIN, lo restablece la **responsable de turno**.
4. Un solo registro alimenta el relevo, los avisos, la evidencia UNE 158101 y el resumen semanal para las familias.
5. Toda corrección de datos de un residente pide **motivo** y guarda versión (tabla `correcciones`). No hay pantalla de edición libre de la ficha: solo «Corregir» por bloque. La edición completa existe solo al dar de alta.
6. Dar de baja (residente o auxiliar) pide motivo y confirmación, y está dentro de un menú «…», nunca al lado de acciones normales.
7. Inicio de dirección: «Hoy necesita tu decisión» muestra **como máximo 5** avisos.
8. Nada interno a la vista del cliente: ni etiquetas MoSCoW (Should, Could), ni precios no decididos.
9. Sin medicación, constantes ni diagnósticos en el MVP.
10. Indicadores de calidad: se muestran como «Ejemplo» hasta validar que se pueden calcular del registro (hipótesis H2).

## Textos
- Español de España, tuteo, frases cortas, sin jerga técnica. Sin género en textos dirigidos a quien dirige («para llegar con todo listo», no «preparada»).
- Categorías del registro: Alimentación e hidratación, Higiene, Movilidad, Sujeción, Descanso, Ánimo, Incidencias.

## Mapa pantalla → archivo
Ver `docs/mapa-pantallas.md`. Si añades o quitas una pantalla, actualiza ese mapa.

## Comprobaciones antes de dar un cambio por hecho
- `php -l` en los archivos tocados y `php artisan test`.
- Revisar la pantalla a 390 px y a 1194 px (panel `turno`) o a 1440 px (panel `direccion`).
- Si el cambio altera un flujo, avisar para actualizar el FigJam y la página de wireframes en Figma.
