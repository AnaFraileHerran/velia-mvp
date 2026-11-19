{{-- Ajustes del panel del turno: objetivos táctiles grandes y hueco para la barra inferior. --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:wght@700&display=swap">
<style>
    /* Atkinson solo donde una cifra mal leída es un error: PIN y habitaciones. */
    .velia-tecla, .velia-numero { font-family: 'Atkinson Hyperlegible Next', 'Albert Sans', sans-serif; }
    .velia-turno .fi-main { padding-bottom: 6rem; }
    .velia-tecla { min-height: 3.5rem; font-size: 1.5rem; border-radius: 9999px; }
    @media (min-width: 768px) { .velia-tecla { min-height: 4.25rem; } }
</style>
<script>document.documentElement.classList.add('velia-turno');</script>
