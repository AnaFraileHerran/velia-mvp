{{-- Aviso de que es la versión de prueba y vuelta a la misma pantalla en la versión actual. --}}
@php($actual = preg_replace('#^/nueva-version#', '/direccion', request()->getRequestUri()))
<a href="{{ $actual }}" class="velia-h-version" title="Abrir esta misma pantalla en la versión actual">
    <span class="velia-h-version-punto" aria-hidden="true"></span>
    Nueva versión
    <span class="velia-h-version-enlace">Ver la actual</span>
</a>
