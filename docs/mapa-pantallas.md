# Mapa de pantallas (wireframes v2 → código)

Fuente: Figma, página «Wireframes v2 · propuesta UX». El nodo sirve para encontrar el wireframe y sus comentarios:
`https://www.figma.com/design/jX0tVZ5A0uheE4VzqXw62X?node-id=<nodo con guion>` (por ejemplo `116-706`).

Cómo se trabaja: un comentario en Figma sobre un wireframe → se cambia el archivo de esta tabla → se responde en el hilo.

## Panel `turno` (auxiliar · tablet o móvil) — `/turno`

| Flujo | Wireframe v2 (nodo tablet · móvil) | Archivo |
|---|---|---|
| A1 · Entrar con PIN | A1 Entrar con PIN (116:500 · 118:305) | `app/Filament/Turno/Pages/Auth/PinLogin.php` · `resources/views/filament/turno/auth/pin-login.blade.php` |
| A0 · Restablecer el PIN | A0 Restablecer PIN (116:548 · 118:416) | `app/Filament/Turno/Acceso/RestablecerPin.php` · `resources/views/filament/turno/auth/restablecer-pin.blade.php` |
| A00 · Primer acceso | A00 Primer acceso 1 y 2 (116:606, 116:653 · 118:347, 118:369) | `app/Filament/Turno/Acceso/PrimerAcceso.php` · `resources/views/filament/turno/auth/primer-acceso.blade.php` |
| A1 · Ponerse al día | A1 Relevo del turno (116:706 · 116:759) | `app/Filament/Turno/Pages/Relevo.php` · `resources/views/filament/turno/pages/relevo.blade.php` |
| A1 · Cómo atender | A1 Cómo atender (117:359 · 117:467) | `app/Filament/Turno/Concerns/ComoAtender.php` · `resources/views/filament/turno/partials/como-atender.blade.php` |
| A2 · Registrar | A2 Residentes lista y plano (116:804, 116:841 · 118:433) | `app/Filament/Turno/Pages/Residentes.php` · `resources/views/filament/turno/pages/residentes.blade.php` · `.../partials/fila-residente.blade.php` |
| A2 · Registrar | A2 Grabar, sin micrófono y Revisar (117:274, 116:887, 117:312 · 117:384, 116:912, 117:422) | `app/Filament/Turno/Pages/Registrar.php` · `resources/views/filament/turno/pages/registrar.blade.php` |
| A3 · Cerrar el turno | A3 Mi turno (116:937 · 118:465) | `app/Filament/Turno/Pages/MiTurno.php` · `resources/views/filament/turno/pages/mi-turno.blade.php` |
| A3 · Cerrar el turno | A3 Cerrar el turno (116:970 · 116:1016) | `app/Filament/Turno/Pages/CerrarTurno.php` · `resources/views/filament/turno/pages/cerrar-turno.blade.php` |
| — | Barra inferior de 3 pestañas y bloqueo a los 5 min | `resources/views/filament/turno/partials/barra-inferior.blade.php` |
| — | Teclado de PIN (compartido) | `resources/views/filament/turno/partials/teclado.blade.php` |

## Panel `direccion` (web) — `/direccion`, en el orden del menú

| Menú | Wireframe v2 (nodo) | Archivo |
|---|---|---|
| 1 · Inicio | Inicio (116:1056) + aviso (119:335) | `app/Filament/Direccion/Pages/Inicio.php` · `app/Filament/Direccion/Widgets/HoyNecesitaTuDecision.php` · `app/Filament/Direccion/Widgets/EstaSemana.php` |
| 2 · Equipo | Equipo (116:1129) | `app/Filament/Direccion/Resources/AuxiliarResource.php` · `.../AuxiliarResource/Pages/*` |
| 3 · Residentes | Lista y ficha (119:387), Corregir (119:480), Dar de baja (119:591), Historial (116:1420), Nuevo residente (116:1466) | `app/Filament/Direccion/Resources/ResidenteResource.php` · `.../ResidenteResource/Pages/*` · `.../ResidenteResource/RelationManagers/*` |
| 3 · Residentes › Ausencias | Ausencias (116:1519) | `app/Filament/Direccion/Resources/AusenciaResource.php` · `.../AusenciaResource/Pages/ManageAusencias.php` |
| 4 · Calidad y auditoría | Calidad y auditoría (116:1261) | `app/Filament/Direccion/Pages/Calidad.php` · `resources/views/filament/direccion/pages/calidad.blade.php` |
| 5 · Familias | Resúmenes (120:342), Enviar (116:1569) | `app/Filament/Direccion/Resources/ResumenResource.php` · `resources/views/filament/direccion/resumen-envio.blade.php` |
| 5 · Familias › Ocupación | Residencia y ocupación (120:401) | `app/Filament/Direccion/Pages/Ocupacion.php` · `resources/views/filament/direccion/pages/ocupacion.blade.php` |
| 6 · Mi residencia | Mi residencia (116:1334) | `app/Filament/Direccion/Pages/MiResidencia.php` · `resources/views/filament/direccion/pages/mi-residencia.blade.php` |

## Asistente IA, voz y navegación contextual (23/11/2026) — Figma: página «Wireframes v3 · Filament 5 + IA»

| Qué | Wireframe v3 | Archivo |
|---|---|---|
| Ayuda · Pregúntale a Velia (chat, dictar, escuchar) | V3 · Ayuda · Pregúntale a Velia / Dictando | `app/Filament/Direccion/Pages/Ayuda.php` · `resources/views/filament/direccion/pages/ayuda.blade.php` · `app/Support/Ia/Asistente.php` |
| Voz común (dictar y leer en voz alta) | — | `resources/views/filament/marca/dictado.blade.php` |
| Inicio · Velia te sugiere (asignar / preguntar) | V3 · Inicio · Velia te sugiere | `app/Filament/Direccion/Widgets/HoyNecesitaTuDecision.php` · `app/Support/Ia/Sugerencias.php` |
| Familias · Redactar con Velia | V3 · Familias · Redactar con Velia | `app/Filament/Direccion/Resources/ResumenResource.php` |
| Calidad · Por dónde empezar | V3 · Calidad · Por dónde empezar | `app/Filament/Direccion/Pages/Calidad.php` · `.../pages/calidad.blade.php` |
| Nuevo residente · Cuéntaselo a Velia | V3 · Nuevo residente · Cuéntaselo a Velia | `ResidenteResource::form` · `.../partials/dictar-ficha.blade.php` · `CreateResidente::rellenarConVelia` |
| Registrar · lo que falta por anotar | V3 · A2 Revisar el registro · lo que falta | `app/Filament/Turno/Pages/Registrar.php` · `.../turno/pages/registrar.blade.php` |
| Paleta «Ir a…» (⌘K) | V3 · Paleta «Ir a…» | `resources/views/filament/direccion/partials/ir-a.blade.php` · `ir-a-boton.blade.php` · `app/Support/Destinos.php` |
| Ficha · Relacionado | V3 · Ficha del residente · Relacionado | `ResidenteResource::infolist` · `Destinos::relacionadosResidente` |
| Bloque «Velia te sugiere» | Componente «Velia te sugiere» | `resources/views/components/velia-sugiere.blade.php` |
| Rastro de la IA (aceptada / cambiada / descartada) | — | `app/Models/SugerenciaIa.php` · migración `2026_11_23_000010` |
| Configuración (simulado / proveedor real) | — | `config/velia.php` · `.env` (`VELIA_IA`, `ANTHROPIC_API_KEY`) |

## Marca y estilos (Design System v2)
- Colores para Filament (cobalto suave, neutros): `app/Support/TemaVelia.php`
- Estilos propios (píldoras, tarjetas, menú): `resources/css/filament/velia.css`, compilado por `resources/css/filament/{direccion,turno}/theme.css` con `npm run build`
- Logo (logotipo monolínea «Ajuste óptico», la v es el isotipo): `resources/views/filament/marca/logo.blade.php` · `public/images/velia-logo.svg`, negativo, símbolo y `public/favicon.svg`
- Tipografía: Albert Sans en los dos paneles; Atkinson Hyperlegible Next solo en el PIN y los números (`resources/views/filament/turno/partials/estilos.blade.php`)

## Lógica compartida
- Estado del turno (qué está registrado, novedades, preguntas): `app/Support/TurnoActual.php`
- Móvil vinculado a una auxiliar: `app/Support/Dispositivo.php`
- Turno abierto al entrar y PIN provisional: `app/Http/Middleware/TurnoAbierto.php`
- Corrección con motivo: `app/Support/Correcciones.php`
- Orden de categorías del registro (simulación de la IA): `app/Support/OrdenarRegistro.php`
- Datos ficticios: `database/seeders/DatabaseSeeder.php`

## Pendiente (no está en el código todavía)
- Huella o Face ID en el móvil (118:369): necesita WebAuthn. Hoy se entra con PIN.
- Indicadores de calidad: se muestran como «Ejemplo» hasta validar H2.
- Envío real de resúmenes por email y WhatsApp corporativo: simulado.
