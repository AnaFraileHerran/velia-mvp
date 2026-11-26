{{--
    «← Volver» desde el segundo nivel (Nueva versión, 26/11/2026). Comentario de Pablo: las migas solas no funcionan con
    personas poco técnicas; Ana quiere mantenerlas. Van juntos: «Volver» lleva a la pantalla de la que vienes (si vienes
    de Velia) y, si has entrado por un enlace directo, a la miga anterior. Aparece en todas las pantallas menos en el Inicio (26/11, Ana).
--}}
<div x-data="{
        destino: null,
        init() {
            const migas = [...document.querySelectorAll('.fi-breadcrumbs a')];
            if (migas.length < 1) return;
            this.destino = migas[migas.length - 1].getAttribute('href');
        },
        volver() {
            const mismaWeb = document.referrer && new URL(document.referrer).origin === location.origin && document.referrer !== location.href;
            if (mismaWeb && history.length > 1) { history.back(); } else { location.href = this.destino; }
        },
    }" x-show="destino" x-cloak class="velia-volver-ctn">
    <button type="button" class="velia-volver" x-on:click="volver()">
        <x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4" />
        Volver
    </button>
</div>
