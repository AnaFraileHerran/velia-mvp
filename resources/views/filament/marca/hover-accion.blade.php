{{-- Color de acción #0F3D95: al pasar el ratón se oscurece a #0B2F75 (Filament lo aclara por defecto). Sin compilar el tema. --}}
<style>
    .fi-btn.fi-btn-color-primary:not(.fi-btn-outlined):not([disabled]):hover,
    .fi-btn.fi-btn-color-primary:not(.fi-btn-outlined):not([disabled]):focus-visible {
        background-color: rgb(11 47 117) !important;
    }
    .fi-btn.fi-btn-color-primary.fi-btn-outlined:not([disabled]):hover {
        background-color: rgb(225 231 244) !important;
    }

    /* Secundarios (decisión 23/11/2026): contorno azul claro #C4CFE8 de la familia del principal, texto en tinta;
       al pasar el ratón, fondo #F0F3FA. El azul pleno queda solo para la acción principal. */
    .fi-btn.fi-color-gray:not(.fi-btn-outlined):not([disabled]) {
        --tw-ring-color: rgb(196 207 232) !important;
    }
    .fi-btn.fi-color-gray:not(.fi-btn-outlined):not([disabled]):hover,
    .fi-btn.fi-color-gray:not(.fi-btn-outlined):not([disabled]):focus-visible {
        background-color: rgb(240 243 250) !important;
        --tw-ring-color: rgb(150 169 212) !important;
    }

    /* Avisos de «Hoy»: texto arriba y botones debajo hasta 1280 px, para que el título no se parta en 4 líneas */
    .velia-aviso { display: flex; flex-direction: column; gap: 12px; }
    .velia-aviso > div:last-child { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    @media (min-width: 1280px) { .velia-aviso { flex-direction: row; align-items: center; } .velia-aviso > div:first-child { flex: 1; } }

    /* Banda «Ahora» del Inicio: celdas en fila, separadas por una línea; se reparten en varias filas si no caben */
    .velia-banda { display: flex; flex-wrap: wrap; background: #fff; border-radius: 12px; box-shadow: 0 0 0 1px rgba(20,20,19,.06); }
    .velia-banda-celda { flex: 1 1 200px; padding: 14px 18px; display: flex; flex-direction: column; gap: 4px; border-left: 1px solid rgb(236 239 244); }
    .velia-banda-celda:first-child { border-left: 0; }
    .velia-banda-titulo { flex: 0 1 190px; background: #fff; border-radius: 12px 0 0 12px; }
    @media (max-width: 767px) { .velia-banda-celda { border-left: 0; border-top: 1px solid rgb(236 239 244); flex-basis: 100%; } .velia-banda-titulo { border-radius: 12px 12px 0 0; border-top: 0; } }

    /* «Hoy» y «Esta semana» con la misma altura en escritorio (23/11/2026) */
    .velia-alto, .velia-alto > div, .velia-alto > div > section, .velia-alto > section { height: 100%; }

    /* Cabeceras de tabla y franjas de filtros (23/11/2026): el gris #F7F8FB se confundía con el fondo de la página.
       Pasan a blanco, como la tarjeta, separadas por una línea; el título de columna, en tinta y seminegrita. */
    .fi-ta-content thead tr, .fi-ta table thead tr { background: #fff !important; }
    .fi-ta-content thead tr { box-shadow: inset 0 -1px 0 rgb(226 230 237); }
    .fi-ta-header-cell-label { color: rgb(20 22 27) !important; }
    .fi-ta-ctn > div[class*="bg-gray-50"], .fi-ta-filter-indicators { background: #fff !important; border-top: 1px solid rgb(236 239 244); border-bottom: 1px solid rgb(236 239 244); }

    /* Menú lateral (23/11/2026): hover y elemento activo en azul de marca suave, no en gris. Activo un paso más intenso que el hover y con barra a la izquierda, para no depender solo del color. */
    .fi-sidebar-item-button:hover, .fi-sidebar-item-button:focus-visible { background: rgb(240 243 250) !important; }
    .fi-sidebar-item-button:hover .fi-sidebar-item-label, .fi-sidebar-item-button:hover .fi-sidebar-item-icon { color: rgb(15 61 149) !important; }
    .fi-sidebar-item-active .fi-sidebar-item-button { background: rgb(225 231 244) !important; box-shadow: inset 3px 0 0 rgb(15 61 149); }
    .fi-sidebar-item-active .fi-sidebar-item-label, .fi-sidebar-item-active .fi-sidebar-item-icon { color: rgb(11 47 117) !important; }
    .fi-sidebar-group-button:hover, .fi-sidebar-group-collapse-button:hover { background: rgb(240 243 250) !important; }
</style>
