<script setup lang="ts">
/**
 * Residentes de la planta: primero los que faltan por registrar (borradores arriba, ya empezados),
 * luego los registrados y los que están fuera. En tablet horizontal, dos columnas.
 */
import { ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Mic } from '@lucide/vue'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import FilaResidente from '../components/FilaResidente.vue'
import ComoAtender from '../components/ComoAtender.vue'

const props = defineProps<{ porRegistrar: any[]; registrados: any[]; fuera: any[] }>()
const yo = usePage<any>().props.yo
const ficha = ref<any>(null)
const abierta = ref(false)
const ver = (r: any) => { ficha.value = r; abierta.value = true }
const total = props.porRegistrar.length + props.registrados.length
</script>

<template>
    <Head title="Residentes" />
    <header class="mb-5">
        <h1 class="text-2xl font-semibold md:text-3xl">Residentes</h1>
        <p class="text-muted-foreground">{{ yo.planta }} · {{ registrados.length }} de {{ total }} registrados</p>
    </header>

    <section class="mb-6">
        <h2 class="mb-2 text-sm font-semibold">Por registrar · {{ porRegistrar.length }}</h2>
        <Card class="gap-0 overflow-hidden py-0">
            <div v-for="(r, i) in porRegistrar" :key="r.id" class="flex items-center" :class="i && 'border-t'">
                <div class="min-w-0 flex-1">
                    <FilaResidente :residente="r" :texto="r.ultimo?.texto" :etiqueta="r.estado === 'borrador' ? 'Borrador' : null" @abrir="ver(r)" />
                </div>
                <Button as-child size="icon-lg" class="mr-3 size-12 shrink-0 rounded-full" :aria-label="`Registrar a ${r.nombre}`">
                    <Link :href="`/auxiliar/registrar/${r.id}`"><Mic class="size-5" /></Link>
                </Button>
            </div>
            <p v-if="!porRegistrar.length" class="p-4 text-sm text-muted-foreground">Todo registrado. Buen turno.</p>
        </Card>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section>
            <h2 class="mb-2 text-sm font-semibold">Registrados · {{ registrados.length }}</h2>
            <Card class="gap-0 overflow-hidden py-0">
                <div v-for="(r, i) in registrados" :key="r.id" :class="i && 'border-t'">
                    <FilaResidente :residente="r" :texto="r.ultimo?.texto" :detalle="r.ultimo ? `A las ${r.ultimo.hora}` : null" :incidencia="r.ultimo?.incidencia" @abrir="ver(r)" />
                </div>
                <p v-if="!registrados.length" class="p-4 text-sm text-muted-foreground">Aún nadie.</p>
            </Card>
        </section>
        <section v-if="fuera.length">
            <h2 class="mb-2 text-sm font-semibold">Fuera · {{ fuera.length }}</h2>
            <Card class="gap-0 overflow-hidden py-0">
                <div v-for="(r, i) in fuera" :key="r.id" :class="i && 'border-t'">
                    <FilaResidente :residente="r" texto="Fuera de la residencia" @abrir="ver(r)" />
                </div>
            </Card>
        </section>
    </div>

    <ComoAtender v-model:open="abierta" :residente="ficha" />
</template>
