{{--
    Paleta «Ir a…» (23/11/2026): navegación contextual. Se abre con ⌘K / Ctrl+K o con el botón de la cabecera.
    Busca pantallas, tareas, pendientes de la auditoría, residentes y personas del equipo. Si no encuentra, ofrece «Preguntar a Velia».
    Como las acciones rápidas de Figma (⌘/): escribes lo que quieres hacer y vas directo.
--}}
@php
    $destinos = \App\Support\Destinos::paraPaleta();
    $ayuda = \App\Filament\Direccion\Pages\Ayuda::getUrl();
@endphp
<div x-data="{
        abierta: false, q: '', sel: 0,
        destinos: @js($destinos),
        norm(s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); },
        get resultados() {
            const q = this.norm(this.q.trim());
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
                <input x-ref="q" x-model="q" x-on:input="sel = 0" type="text" placeholder="¿Adónde quieres ir? Una pantalla, un residente, una tarea…"
                    x-on:keydown.down.prevent="mover(1)" x-on:keydown.up.prevent="mover(-1)" x-on:keydown.enter.prevent="ir(resultados[sel])"
                    role="combobox" aria-controls="velia-ir-a-lista" aria-autocomplete="list">
                <div id="velia-ir-a-lista" class="velia-ir-a-lista" role="listbox">
                    <template x-for="(d, i) in resultados" :key="d.id">
                        <div>
                            <p class="velia-ir-a-grupo" x-show="i === 0 || resultados[i - 1].grupo !== d.grupo" x-text="d.grupo"></p>
                            <button type="button" class="velia-ir-a-item" :id="'velia-ir-a-' + i" role="option" :aria-selected="sel === i" x-on:mouseenter="sel = i" x-on:click="ir(d)">
                                <span x-text="d.titulo"></span>
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
