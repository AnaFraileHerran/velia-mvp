<script setup lang="ts">
/**
 * Mi planta (26/11/2026, decisión 1): Resumen y Residentes en una sola pantalla y una sola lista.
 * 1) Lo que espera respuesta (pregunta de la dirección, encargos, quién vuelve hoy).
 * 2) La planta: con incidencia (lo anotado por una compañera va dentro de la fila), por registrar (borradores primero),
 *    registrados y fuera. Cada residente aparece una sola vez. En tablet horizontal, registrados y fuera en dos columnas.
 */
import { ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ChevronDown, MessageSquareReply, StickyNote } from '@lucide/vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import FilaResidente from '../components/FilaResidente.vue'

const props = defineProps<{
    saludo: string; fecha: string; preguntas: any[]; asignados: any[]; vuelvenHoy: any[]; notas: any[]
    conNovedad: any[]; porRegistrar: any[]; registrados: any[]; fuera: any[]; total: number; hechos: number
}>()
const yo = usePage<any>().props.yo
const respuestas = ref<Record<number, string>>({})
const responder = (id: number) => router.post(`/auxiliar/preguntas/${id}`, { respuesta: respuestas.value[id] ?? '' }, { preserveScroll: true, onSuccess: () => delete respuestas.value[id] })
const post = (url: string) => router.post(url, {}, { preserveScroll: true })
const pct = props.total ? Math.round((props.hechos / props.total) * 100) : 0
const etiqueta = (r: any) => (r.estado === 'borrador' ? 'Borrador' : r.estado === 'registrado' ? 'Registrado' : null)
</script>

<template>
    <Head title="Mi planta" />
    <header class="mb-5">
        <h1 class="text-2xl font-semibold md:text-3xl">{{ saludo }}</h1>
        <p class="text-muted-foreground">{{ yo.planta }} · Turno de {{ yo.turno.toLowerCase() }} · {{ fecha }}</p>
        <div class="mt-3 flex items-center gap-3">
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted" role="progressbar" :aria-valuenow="pct" aria-valuemin="0" aria-valuemax="100" :aria-label="`${hechos} de ${total} registrados`">
                <div class="h-full rounded-full bg-success transition-all" :style="{ width: pct + '%' }" />
            </div>
            <span class="text-sm font-medium">{{ hechos }} de {{ total }} registrados</span>
        </div>
    </header>

    <div class="space-y-4">
        <!-- 1 · Lo que espera respuesta -->
        <Card v-for="p in preguntas" :key="p.id" class="border-primary/40 bg-secondary">
            <CardHeader>
                <CardDescription class="font-semibold text-primary">La dirección pregunta<span v-if="p.residente"> · {{ p.residente }}</span></CardDescription>
                <CardTitle class="text-base leading-snug">{{ p.texto }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
                <Textarea v-model="respuestas[p.id]" rows="2" placeholder="Responde en pocas palabras" class="bg-card text-base" />
                <Button class="h-11 rounded-full" :disabled="!(respuestas[p.id] || '').trim()" @click="responder(p.id)"><MessageSquareReply /> Responder</Button>
            </CardContent>
        </Card>

        <Card v-for="a in asignados" :key="'a' + a.id" class="gap-3 py-4">
            <CardContent class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-muted-foreground">Te lo han encargado<span v-if="a.vence"> · para el {{ a.vence }}</span></p>
                    <p class="font-medium">{{ a.titulo }}<span v-if="a.residente"> · {{ a.residente }}</span></p>
                </div>
                <Button variant="outline" class="h-11 rounded-full" @click="post(`/auxiliar/avisos/${a.id}/hecho`)">Hecho</Button>
            </CardContent>
        </Card>

        <Card v-for="v in vuelvenHoy" :key="'v' + v.id" class="gap-3 py-4">
            <CardContent class="flex items-center justify-between gap-3">
                <p><span class="font-medium">{{ v.residente }}</span> vuelve hoy</p>
                <Button variant="outline" class="h-11 rounded-full" @click="post(`/auxiliar/ausencias/${v.id}/vuelto`)">Ha vuelto</Button>
            </CardContent>
        </Card>

        <Collapsible v-if="notas.length" class="rounded-xl border bg-card">
            <CollapsibleTrigger class="flex min-h-12 w-full items-center justify-between gap-2 px-4 font-semibold">
                <span class="flex items-center gap-2"><StickyNote class="size-4" /> Nota del relevo · {{ notas[0].quien?.split(' ')[0] }}, {{ notas[0].hora }}</span>
                <ChevronDown class="size-4" />
            </CollapsibleTrigger>
            <CollapsibleContent>
                <div v-for="(n, i) in notas" :key="i" class="border-t px-4 py-3">
                    <p class="text-xs text-muted-foreground">{{ n.quien }} · a las {{ n.hora }}</p>
                    <p class="whitespace-pre-line text-sm">{{ n.nota }}</p>
                </div>
            </CollapsibleContent>
        </Collapsible>

        <!-- 2 · La planta, en una sola lista -->
        <section v-if="conNovedad.length">
            <h2 class="mb-2 text-sm font-semibold text-destructive">Con incidencia · {{ conNovedad.length }} <span class="font-normal text-muted-foreground">· de las últimas 12 horas</span></h2>
            <Card class="gap-0 overflow-hidden py-0">
                <div v-for="(r, i) in conNovedad" :key="r.id" :class="i && 'border-t'">
                    <FilaResidente :residente="r" :texto="r.novedad.texto" :detalle="`Lo anotó ${r.novedad.quien} a las ${r.novedad.hora}`" :incidencia="r.novedad.incidencia"
                        :etiqueta="etiqueta(r)" :micro="r.estado !== 'registrado'" />
                </div>
            </Card>
        </section>

        <section>
            <h2 class="mb-2 text-sm font-semibold">Por registrar · {{ porRegistrar.length }}</h2>
            <Card class="gap-0 overflow-hidden py-0">
                <div v-for="(r, i) in porRegistrar" :key="r.id" :class="i && 'border-t'">
                    <FilaResidente :residente="r" :texto="r.ultimo ? `Último: ${r.ultimo.texto}` : null" :detalle="r.ultimo ? `a las ${r.ultimo.hora}` : null" :etiqueta="etiqueta(r)" micro />
                </div>
                <p v-if="!porRegistrar.length" class="p-4 text-sm text-muted-foreground">Todo registrado. Buen turno.</p>
            </Card>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section v-if="registrados.length">
                <h2 class="mb-2 text-sm font-semibold">Registrados · {{ registrados.length }}</h2>
                <Card class="gap-0 overflow-hidden py-0">
                    <div v-for="(r, i) in registrados" :key="r.id" :class="i && 'border-t'">
                        <FilaResidente :residente="r" :texto="r.ultimo?.texto" :detalle="r.ultimo ? `A las ${r.ultimo.hora}` : null" :incidencia="r.ultimo?.incidencia" />
                    </div>
                </Card>
            </section>
            <section v-if="fuera.length">
                <h2 class="mb-2 text-sm font-semibold">Fuera · {{ fuera.length }}</h2>
                <Card class="gap-0 overflow-hidden py-0">
                    <div v-for="(r, i) in fuera" :key="r.id" :class="i && 'border-t'">
                        <FilaResidente :residente="r" texto="Fuera de la residencia" />
                    </div>
                </Card>
            </section>
        </div>

        <p class="pt-2 text-center text-sm text-muted-foreground">
            ¿Terminas tu turno? <Link href="/auxiliar/mi-turno" class="font-medium text-primary underline underline-offset-2">Ve a Mi turno</Link>
        </p>
    </div>
</template>
