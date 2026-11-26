{{--
    Paleta «Ir a…» (23/11/2026): navegación contextual. Se abre con ⌘K / Ctrl+K o con el botón de la cabecera.
    Busca pantallas, tareas, pendientes de la auditoría, residentes y personas del equipo. Si no encuentra, ofrece «Preguntar a Velia».
    Como las acciones rápidas de Figma (⌘/): escribes lo que quieres hacer y vas directo.
--}}
@php
    $destinos = \App\Support\Destinos::paraPaleta();
    $ayuda = \App\Filament\Direccion\Pages\Ayuda::getUrl();
    // Flujo nuevo (versión H, comentario de Pablo): respuesta rápida del asistente encima de los resultados, como el modo IA de un buscador.
    // Solo se envía la pregunta escrita, nunca datos de residentes (regla 10). La búsqueda por significado queda para más adelante.
    $rapida = \App\Support\VersionH::activa() ? route('filament.direccion-h.respuesta-rapida') : null;
    // 26/11 (patrón de Santa Plata): resultados agrupados por tipo con recuento y un resumen de Velia. Se calculan con los datos
    // de la residencia en el servidor, sin proveedor de IA (regla 10).
    $buscar = \App\Support\VersionH::activa() ? route('filament.direccion-h.buscar') : null;
@endphp
<div x-data="{
        abierta: false, q: '', sel: 0, respuesta: null, pensando: false, temporizador: null,
        rapida: @js($rapida), buscarUrl: @js($buscar), datos: null, tBuscar: null,
        pedirResultados() {
            clearTimeout(this.tBuscar);
            if (!this.buscarUrl) return;
            const q = this.q.trim();
            if (q.length < 2) { this.datos = null; return; }
            this.tBuscar = setTimeout(async () => {
                try { const r = await fetch(this.buscarUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }); if (q === this.q.trim()) this.datos = await r.json(); } catch (e) {}
            }, 200);
        },
        esPregunta(t) { t = this.norm(t.trim()); return t.length > 8 && (t.includes('?') || /^(como|que|donde|cuando|por que|quien|cual|puedo|hay)\b/.test(t) || t.split(' ').length >= 4); },
        pedirRespuesta() {
            clearTimeout(this.temporizador); this.respuesta = null;
            if (!this.rapida || !this.esPregunta(this.q)) return;
            const q = this.q.trim();
            this.temporizador = setTimeout(async () => {
                this.pensando = true;
                try { const r = await fetch(this.rapida + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }); if (q === this.q.trim()) this.respuesta = await r.json(); } catch (e) {}
                this.pensando = false;
            }, 500);
        },
        destinos: @js($destinos),
        norm(s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); },
        get resultados() {
            const q = this.norm(this.q.trim());
            if (this.buscarUrl && q.length >= 2 && this.datos) {
                const lista = [];
                this.datos.grupos.forEach((g, gi) => {
                    g.items.forEach((it, ii) => lista.push({ id: 'g' + gi + '-' + ii, titulo: it.titulo, detalle: it.detalle, url: it.url, grupo: g.nombre + ' · ' + g.total }));
                    if (g.verTodos) lista.push({ id: 'g' + gi + '-todos', titulo: 'Ver los ' + g.total + ' →', url: g.verTodos, grupo: g.nombre + ' · ' + g.total, todos: true });
                });
                lista.push({ id: 'preguntar', titulo: 'Seguir la conversación con Velia: «' + this.q.trim() + '»', grupo: 'Asistente', url: '{{ $ayuda }}?pregunta=' + encodeURIComponent(this.q.trim()) });
                return lista;
            }
            let lista = !q ? this.destinos.filter(d => d.grupo !== 'Residentes' && d.grupo !== 'Equipo')
                : this.destinos.filter(d => q.split(' ').every(p => this.norm(d.titulo + ' ' + d.claves).includes(p)));
            const orden = ['Pendiente', 'Residentes', 'Equipo', 'Hacer', 'Ir a'];
            lista = lista.map((d, i) => [d, i]).sort((a, b) => (orden.indexOf(a[0].grupo) - orden.indexOf(b[0].grupo)) || (a[1] - b[1])).map(x => x[0]);
            lista = lista.slice(0, 12);
            if (q) lista.push({ id: 'preguntar', titulo: 'Preguntar a Velia: «' + this.q.trim() + '»', grupo: 'Asistente', url: '{{ $ayuda }}?pregunta=' + encodeURIComponent(this.q.trim()) });
            return lista;
        },
        abrir() { this.abierta = true; this.q = ''; this.sel = 0; this.$nextTick(() => this.$refs.q.focus()); },
        ir(d) { if (d) window.location.href = d.url; },
        mover(n) { const t = this.resultados.length; this.sel = (this.sel + n + t) % t; this.$nextTick(() => document.getElementById('velia-ir-a-' + this.sel)?.scrollIntoView({ block: 'nearest' })); },
    }"
    x-on:keydown.window.meta.k.prevent="abrir()" x-on:keydown.window.ctrl.k.prevent="abrir()" x-on:velia-ir-a.window="abrir()">
    <template x-teleport="body">
        <div x-show="abierta" x-cloak class="velia-ir-a-fondo" x-on:click.self="abierta = false" x-on:keydown.escape.window="abierta = false">
            <div class="velia-ir-a-caja" role="dialog" aria-modal="true" aria-label="Ir a">
                <input x-ref="q" x-model="q" x-on:input="sel = 0; pedirRespuesta(); pedirResultados()" type="text" placeholder="{{ $rapida ? 'Busca o pregunta: un residente, una pantalla, «¿cómo restablezco un PIN?»…' : '¿Adónde quieres ir? Una pantalla, un residente, una tarea…' }}"
                    x-on:keydown.down.prevent="mover(1)" x-on:keydown.up.prevent="mover(-1)" x-on:keydown.enter.prevent="ir(resultados[sel])"
                    role="combobox" aria-controls="velia-ir-a-lista" aria-autocomplete="list">
                {{-- Resumen de lo encontrado (si no es una pregunta): una línea, como el bloque de arriba de Santa Plata --}}
                <div x-show="buscarUrl && datos && datos.resumen && !(pensando || respuesta)" x-cloak class="velia-ir-a-respuesta" aria-live="polite">
                    <p class="velia-ir-a-respuesta-cabecera">Velia resume <span class="velia-sugiere-ejemplo">Ejemplo</span></p>
                    <p x-text="datos && datos.resumen" class="text-sm text-gray-800"></p>
                </div>
                <div x-show="rapida && (pensando || respuesta)" x-cloak class="velia-ir-a-respuesta" aria-live="polite">
                    <p class="velia-ir-a-respuesta-cabecera">
                        Velia responde
                        <span x-show="respuesta && respuesta.ejemplo" class="velia-sugiere-ejemplo">Ejemplo</span>
                    </p>
                    <p x-show="pensando" class="text-sm text-gray-500">Pensando…</p>
                    <p x-show="respuesta" x-text="respuesta && respuesta.texto" class="text-sm text-gray-800"></p>
                    <div x-show="respuesta && respuesta.enlaces && respuesta.enlaces.length" class="flex flex-wrap gap-x-3 gap-y-1 pt-1">
                        <template x-for="e in (respuesta ? respuesta.enlaces : [])" :key="e.url"><a :href="e.url" class="text-sm font-medium text-primary-700 hover:underline" x-text="e.titulo + ' →'"></a></template>
                    </div>
                </div>
                <div id="velia-ir-a-lista" class="velia-ir-a-lista" role="listbox">
                    <template x-for="(d, i) in resultados" :key="d.id">
                        <div>
                            <p class="velia-ir-a-grupo" x-show="i === 0 || resultados[i - 1].grupo !== d.grupo" x-text="d.grupo"></p>
                            <button type="button" class="velia-ir-a-item" :id="'velia-ir-a-' + i" role="option" :aria-selected="sel === i" x-on:mouseenter="sel = i" x-on:click="ir(d)">
                                <span class="min-w-0"><span x-text="d.titulo" :class="d.todos && 'font-semibold text-primary-700'"></span><span x-show="d.detalle" x-text="d.detalle" class="block truncate text-xs text-gray-500"></span></span>
                                <span x-show="sel === i" class="text-xs" aria-hidden="true">↵</span>
                            </button>
                        </div>
                    </template>
                </div>
                <p class="velia-ir-a-pie">↑ ↓ para moverte · ↵ para ir · Esc para cerrar</p>
            </div>
        </div>
    </template>
</div>
