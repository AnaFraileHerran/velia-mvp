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


## Pantallas reales por flujo para comentar (Figma, 23/11/2026)

Página «Velia · Pantallas por flujo (localhost · 23/11/2026)» (id `137:184`) del archivo `jX0tVZ5A0uheE4VzqXw62X`. Cada pantalla es una captura editable del localhost; su nombre empieza por el código. Enlace a una pantalla: `https://www.figma.com/design/jX0tVZ5A0uheE4VzqXw62X?node-id=<nodo con guion>`.

| Código | Pantalla | Nodo | Archivo |
|---|---|---|---|
| A00-01 | Primer acceso · tablet | `173:2` | `app/Filament/Turno/Acceso/PrimerAcceso.php · resources/views/filament/turno/auth/primer-acceso.blade.php` |
| A00-02 | Primer acceso · móvil | `176:2` | `ídem` |
| A0-01 | Restablecer el PIN · tablet | `174:2` | `app/Filament/Turno/Acceso/RestablecerPin.php · resources/views/filament/turno/auth/restablecer-pin.blade.php` |
| A0-02 | Restablecer el PIN · móvil | `177:2` | `ídem` |
| A1E-01 | Entrar con PIN · tablet | `175:2` | `app/Filament/Turno/Pages/Auth/PinLogin.php · resources/views/filament/turno/auth/pin-login.blade.php` |
| A1E-02 | Entrar con PIN · móvil | `178:2` | `ídem` |
| A1-01 | Relevo del turno · tablet | `188:2` | `app/Filament/Turno/Pages/Relevo.php · resources/views/filament/turno/pages/relevo.blade.php` |
| A1-02 | Cómo atender · tablet | `189:2` | `app/Filament/Turno/Concerns/ComoAtender.php · resources/views/filament/turno/partials/como-atender.blade.php` |
| A1-03 | Relevo del turno · móvil | `179:2` | `como A1-01` |
| A1-04 | Cómo atender · móvil | `180:2` | `como A1-02` |
| A2-01 | Residentes · lista · tablet | `190:2` | `app/Filament/Turno/Pages/Residentes.php · resources/views/filament/turno/pages/residentes.blade.php · .../partials/fila-residente.blade.php` |
| A2-02 | Residentes · plano de planta · tablet | `191:2` | `como A2-01` |
| A2-03 | Grabar el registro · tablet | `192:2` | `app/Filament/Turno/Pages/Registrar.php · resources/views/filament/turno/pages/registrar.blade.php` |
| A2-04 | Escribir (sin micrófono) · tablet | `193:2` | `como A2-03` |
| A2-05 | Revisar lo que ordena la IA + lo que falta · tablet | `194:2` | `como A2-03 · app/Support/OrdenarRegistro.php · app/Support/Ia/Sugerencias.php` |
| A2-06 | Residentes · móvil | `181:2` | `como A2-01` |
| A2-07 | Grabar el registro · móvil | `182:2` | `como A2-03` |
| A2-08 | Escribir (sin micrófono) · móvil | `183:2` | `como A2-03` |
| A2-09 | Revisar lo que ordena la IA + lo que falta · móvil | `185:2` | `como A2-05` |
| A3-01 | Mi turno · tablet | `195:2` | `app/Filament/Turno/Pages/MiTurno.php · resources/views/filament/turno/pages/mi-turno.blade.php` |
| A3-02 | Cerrar el turno · tablet | `196:2` | `app/Filament/Turno/Pages/CerrarTurno.php · resources/views/filament/turno/pages/cerrar-turno.blade.php` |
| A3-03 | Mi turno · móvil | `186:2` | `como A3-01` |
| A3-04 | Cerrar el turno · móvil | `187:2` | `como A3-02` |
| D1-01 | Acceso de la dirección | `172:2` | `app/Providers/Filament/DireccionPanelProvider.php (->login() de Filament)` |
| INI-01 | Inicio · Hoy necesita tu decisión (con «Velia te sugiere») | `140:2` | `app/Filament/Direccion/Pages/Inicio.php · app/Filament/Direccion/Widgets/*.php · resources/views/filament/direccion/widgets/*.blade.php` |
| INI-02 | D2 · Preguntar al turno (rellenado por Velia) | `163:2` | `HoyNecesitaTuDecision.php (preguntarAction) · app/Support/Ia/Sugerencias.php` |
| INI-03 | D2 · No procede con motivo | `165:2` | `HoyNecesitaTuDecision.php (noProcedeAction)` |
| INI-04 | Turno sin abrir · ver quién falta | `164:2` | `HoyNecesitaTuDecision.php (quienFaltaAction) · .../widgets/quien-falta.blade.php · app/Support/TurnosSinAbrir.php` |
| EQ-01 | Equipo · lista | `141:2` | `app/Filament/Direccion/Resources/AuxiliarResource.php · .../AuxiliarResource/Pages/ListAuxiliares.php` |
| EQ-02 | Equipo · enfermería y medicina | `142:2` | `como EQ-01` |
| EQ-03 | Ficha de la auxiliar | `143:2` | `AuxiliarResource.php (infolist) · .../Pages/ViewAuxiliar.php · resources/views/filament/direccion/equipo/*` |
| EQ-04 | «No puede entrar» (PIN o móvil) | `167:2` | `AuxiliarResource.php (formNoPuedeEntrar, resolverAcceso)` |
| EQ-05 | Editar ficha | `160:2` | `AuxiliarResource.php (form) · .../Pages/EditAuxiliar.php` |
| EQ-06 | Añadir auxiliar | `144:2` | `AuxiliarResource.php (form) · .../Pages/CreateAuxiliar.php` |
| RES-01 | Residentes · lista | `145:2` | `app/Filament/Direccion/Resources/ResidenteResource.php (table) · .../Pages/ListResidentes.php` |
| RES-02 | Ficha del residente con «Relacionado» | `146:2` | `ResidenteResource.php (infolist) · .../Pages/ViewResidente.php · app/Support/Destinos.php` |
| RES-03 | Corregir un dato con motivo | `166:2` | `ResidenteResource.php (corregirAction) · app/Support/Correcciones.php` |
| RES-04 | Historial de registros | `148:2` | `ResidenteResource/RelationManagers/RegistrosRelationManager.php` |
| RES-05 | Correcciones | `149:2` | `ResidenteResource/RelationManagers/CorreccionesRelationManager.php` |
| RES-06 | Autorizados de la familia | `158:2` | `ResidenteResource/RelationManagers/ContactosRelationManager.php` |
| RES-07 | Nuevo residente · rellenado por Velia | `157:2` | `ResidenteResource.php (form) · .../Pages/CreateResidente.php · resources/views/filament/direccion/partials/dictar-ficha.blade.php` |
| RES-08 | Ausencias (D5) | `154:2` | `app/Filament/Direccion/Resources/AusenciaResource.php · .../Pages/ManageAusencias.php` |
| CAL-01 | Calidad y auditoría (con «Por dónde empezar») | `150:2` | `app/Filament/Direccion/Pages/Calidad.php · resources/views/filament/direccion/pages/calidad.blade.php · app/Support/PendientesAuditoria.php` |
| FAM-01 | Familias · resúmenes semanales | `151:2` | `app/Filament/Direccion/Resources/ResumenResource.php` |
| FAM-02 | D3 · Redactar con Velia | `169:2` | `ResumenResource.php (redactar) · app/Support/Ia/Sugerencias.php` |
| FAM-03 | D3 · Editar el resumen | `170:2` | `ResumenResource.php (editar)` |
| FAM-04 | Residencia y ocupación | `153:2` | `app/Filament/Direccion/Pages/Ocupacion.php · resources/views/filament/direccion/pages/ocupacion.blade.php` |
| MIR-01 | Mi residencia | `152:2` | `app/Filament/Direccion/Pages/MiResidencia.php · resources/views/filament/direccion/pages/mi-residencia.blade.php` |
| AYU-01 | Ayuda · Pregúntale a Velia | `155:2` | `app/Filament/Direccion/Pages/Ayuda.php · resources/views/filament/direccion/pages/ayuda.blade.php · resources/views/filament/marca/dictado.blade.php` |
| AYU-02 | Ayuda · respuesta del asistente | `156:2` | `Ayuda.php · app/Support/Ia/Asistente.php` |
| NAV-01 | Paleta «Ir a…» (⌘K) | `171:2` | `resources/views/filament/direccion/partials/ir-a.blade.php · ir-a-boton.blade.php · app/Support/Destinos.php` |

## Versión H de dirección (25/11/2026) · `/direccion-h`

Mismas pantallas y datos que `/direccion`, con patrones de uso de Holded para compararlas en un test. Panel: `app/Providers/Filament/DireccionHPanelProvider.php`. Lo que cambia solo en H se decide con `App\Support\VersionH::activa()`.

| Patrón | Dónde | Archivo |
|---|---|---|
| Aviso «Versión H · prueba» y enlace a la misma pantalla en la actual | Barra superior | `resources/views/filament/direccion-h/version.blade.php` |
| «+ Crear» global: residente, salida, auxiliar, enfermería o medicina | Barra superior | `resources/views/filament/direccion-h/crear.blade.php` |
| Inicio con pestañas Hoy · Esta semana · Auditoría, con contador | Inicio | `Pages/Inicio.php` (`content()`, `getWidgets()`), `direccion-h/pestanas-inicio.blade.php`, `Widgets/LoQueFaltaAuditoria.php` |
| Listas con pestañas de estado y contador | Residentes, Familias | `ListResidentes.php`, `ManageResumenes.php` (`getTabs()`) |
| Botón «Ayuda» por sección (icono de interrogación, el mismo del menú), con la pregunta lista para Velia | Cabecera de cada pantalla | `direccion-h/ayuda-seccion.blade.php`, textos en `VersionH::ayudaSeccion()` |

### Flujo nuevo del Inicio y comentarios de Figma (25/11/2026)

| Cambio | Comentario | Archivo |
|---|---|---|
| Inicio H: «Ahora» compacto con la alerta de turno dentro (sin banda roja) | Pablo · INI-01 | `Widgets/AhoraH.php`, `widgets/ahora-h.blade.php` |
| Inicio H: «Próximamente» Semana · Mes · Trimestre, auditoría como hito con importancia | Pablo · INI-01 | `Widgets/ProximamenteH.php`, `widgets/proximamente-h.blade.php` |
| Inicio H: «Cómo va tu residencia con Velia» (servicio + valor; «Ejemplo» donde no hay dato) | Pablo · INI-01 | `Widgets/ValorVeliaH.php`, `widgets/valor-velia-h.blade.php` |
| «Velia sugiere» en una línea (H) | Pablo · INI-01 | `components/velia-sugiere-linea.blade.php` |
| Avisos de dirección: equipo, familia, organización, patrones de planta | Pablo · INI-01 | `Models/Aviso.php`, `Support/Demo/AvisosTransversales.php` |
| Un solo buscador «Buscar o preguntar a Velia» con respuesta rápida (H) | Pablo y Antonio · cabecera | `direccion-h/buscar.blade.php`, `partials/ir-a.blade.php`, ruta `respuesta-rapida` |
| Menú lateral de 14rem, siempre abierto (H) | Pablo y Antonio · INI-01 | `DireccionHPanelProvider.php` |
| Equipo: «Añadir otro rol» (áreas como Ocio; rol `otro`, sin acceso a la app) | Ana · EQ-01 | `AuxiliarResource.php`, `ListAuxiliares.php`, `Models/AreaEquipo.php` |
| Turno: botón flotante de micrófono → elegir residente → graba | Antonio · A1-02 | `turno/partials/boton-grabar.blade.php`, `Turno/Pages/Registrar.php` |
| Cómo atender: icono junto a cada dato | Antonio · A1-04 | `turno/partials/como-atender.blade.php` |
| Landing de dirección: pantalla de éxito al enviar | Ana · LAND-D | `public/landing/direccion.html` |
