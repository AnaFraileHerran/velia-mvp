# Velia · MVP (Laravel + Filament)

Software para residencias de mayores de Madrid con menos de 50 plazas. Producto de Icarus.
Este repositorio es la fuente de los wireframes funcionales: cada pantalla de «Wireframes v2 · propuesta UX» (Figma) es un archivo de este proyecto. Cuando el equipo pida un cambio de pantalla, se cambia aquí.

## Referencias
- Wireframes v2 (Figma, página «Wireframes v2 · propuesta UX»): https://www.figma.com/design/jX0tVZ5A0uheE4VzqXw62X
- Flujos y MoSCoW (FigJam): https://www.figma.com/board/pUdiiOvLcHCzL0mtFtyhps
- Prototipo anterior (referencia de contenido): https://frabjous-selkie-afb6cd.netlify.app/
- Estilos: Filament v5 (https://filamentphp.com/docs/5.x). Usa sus componentes y su tema; no inventes CSS si Filament ya lo resuelve.

## Stack
- Laravel 13, PHP 8.3+ (en el Mac de Ana, 8.5), Filament 5, Livewire 4, Tailwind 4 (tema con @tailwindcss/vite), SQLite en local.
- Migrado de Laravel 12 + Filament 3.3 el 23/11/2026 (rama `migracion-filament-5`). En páginas, `$view` ya no es estático; formularios y fichas usan `Schema`.
- Dos paneles de Filament:
  - `direccion` en `/direccion`: web de la dirección, entra con email y contraseña.
  - `direccion-h` en `/nueva-version`: «Nueva versión» de la web de dirección (patrones de Holded y comentarios del equipo del 25/11), en paralelo a `/direccion` para compararlas. Mismos recursos y datos; lo que cambia solo aquí se decide con `VersionH::activa()`.
  - `turno` en `/turno`: app de las auxiliares para tablet compartida o móvil, entra con PIN.
- App de la auxiliar con **shadcn-vue** en `/auxiliar` (25/11/2026, decisión de Ana: el front de la auxiliar en tablet y móvil es shadcn-vue, no Filament). Inertia + Vue 3 sobre la misma app de Laravel, sesión del guard `turno`. Va en paralelo a `/turno` hasta validarla. Código: `routes/auxiliar.php`, `app/Http/Controllers/Auxiliar/`, `resources/js/auxiliar/` (pantallas y componentes) y `resources/js/components/ui/` (componentes de shadcn-vue, se añaden con `npx shadcn-vue@latest add …`). Tema: `resources/css/auxiliar.css` (tokens de `docs/tema-shadcn-vue.css`). Para esta parte, la regla «usa Filament» no aplica: usa los componentes de shadcn-vue.

## App de la auxiliar (/auxiliar) · flujo del 26/11/2026
- Barra: Mi planta · micro · Mi turno. Mi planta une Resumen y Residentes.
- No se abre ni se cierra turno: empieza al entrar con PIN y termina solo a las 9 h (`AuxiliarConTurno`). «Salir» cambia de persona.
- «Sin novedad» solo en bloque al final del turno, con confirmación. Guía de la primera vez por persona (`users.guia_vista_at`).
- Registrar: «No puedo grabar: escribir» lleva a un campo para escribirlo con sus palabras (Velia lo ordena) y, debajo, los apartados con chips como ayuda opcional.

## Familias y comunicaciones (26/11/2026, tarde)
- La sección se llama «Familias». Además del directorio guarda cada comunicación con la familia (tabla `comunicaciones`: fecha, qué, a quién, canal, quién la anota). No se edita ni se borra.
- El resumen semanal enviado se anota solo como comunicación. Tras una caída confirmada en `/auxiliar` se crea el aviso `informar_familia` en Inicio, con «Anotar que se ha informado».
- El aviso de planta sin nadie se muestra como «Planta sin actividad» (la clave sigue siendo `turno_sin_abrir`).

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
10. IA: la IA propone y la persona confirma. Toda sugerencia se acepta, se cambia o se descarta, y se anota con `SugerenciaIa::anotar()`. Simulada por defecto (`config/velia.php`); con proveedor real, el asistente de Ayuda solo envía la pregunta, nunca datos de residentes.
11. Indicadores de calidad: se muestran como «Ejemplo» hasta validar que se pueden calcular del registro (hipótesis H2).

## Textos
- Español de España, tuteo, frases cortas, sin jerga técnica. Sin género en textos dirigidos a quien dirige («para llegar con todo listo», no «preparada»).
- Categorías del registro: Alimentación e hidratación, Higiene, Movilidad, Sujeción, Descanso, Ánimo, Incidencias.

## Mapa pantalla → archivo
Ver `docs/mapa-pantallas.md`. Si añades o quitas una pantalla, actualiza ese mapa.

## Comprobaciones antes de dar un cambio por hecho
- `php -l` en los archivos tocados y `php artisan test`.
- Revisar la pantalla a 390 px y a 1194 px (panel `turno`) o a 1440 px (panel `direccion`).
- Si el cambio altera un flujo, avisar para actualizar el FigJam y la página de wireframes en Figma.

## Cambios pedidos en Figma (comentarios del equipo)
El equipo comenta en la página «Velia · Pantallas por flujo (localhost)» del archivo de Figma `jX0tVZ5A0uheE4VzqXw62X` (id de página `137:184`). Cuando pidan «aplica los comentarios de Figma»:
1. Lee los comentarios sin resolver de esa página (`get_comments` del MCP de Figma, `pageId: 137:184`, `resolved: false`).
2. Para cada comentario, localiza la pantalla por el nodo o por el código del principio (p. ej. «RES-02:») y busca su archivo en `docs/mapa-pantallas.md` (tabla «Pantallas reales por flujo»).
3. Antes de cambiar nada, aplica las reglas de producto de arriba. Si el comentario es una duda, contradice una regla o no está claro, no toques el código: déjalo como pregunta.
4. Aplica el cambio, pasa `php artisan test` y revisa la pantalla en local.
5. Devuelve una tabla: código · comentario (resumido) · qué se ha cambiado y en qué archivo · pendiente o pregunta. Quien revisa marca el comentario como resuelto en Figma.
6. Si el cambio altera la pantalla, vuelve a capturarla con `docs/figma/captura-pantallas.js` (instrucciones dentro) y sustituye el marco con el mismo código.
