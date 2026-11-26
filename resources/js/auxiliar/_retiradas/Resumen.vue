<script setup lang="ts">
/**
 * Resumen del turno (pantalla de inicio de la auxiliar). Jerarquía:
 * 1) lo que espera respuesta (pregunta de la dirección, encargos), 2) residentes con novedades (incidencias primero),
 * 3) plegado: vuelven hoy, notas de compañeras y sin novedades.
 */
import { ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { ChevronDown, MessageSquareReply } from '@lucide/vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import FilaResidente from '../components/FilaResidente.vue'
import ComoAtender from '../components/ComoAtender.vue'

defineProps<{ saludo: string; fecha: string; preguntas: any[]; asignados: any[]; novedades: any[]; vuelvenHoy: any[]; notas: any[]; sinNovedades: any[] }>()
const yo = usePage<any>().props.yo
const respuestas = ref<Record<number, string>>({})
const ficha = ref<any>(null)
const abierta = ref(false)
const ver = (r: any) => { ficha.value = r; abierta.value = true }
const responder = (id: number) => router.post(`/auxiliar/preguntas/${id}`, { respuesta: respuestas.value[id] ?? '' }, { preserveScroll: true, onSuccess: () => delete respuestas.value[id] })
const post = (url: string) => router.post(url, {}, { preserveScroll: true })
</script>

<template>
    <Head title="Resumen" />
    <header class="mb-5">
        <h1 class="text-2xl font-semibold md:text-3xl">{{ saludo }}</h1>
        <p class="text-muted-foreground">{{ yo.planta }} · Turno de {{ yo.turno.toLowerCase() }} · {{ fecha }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <Badge variant="secondary">{{ novedades.length }} {{ novedades.length === 1 ? 'novedad' : 'novedades' }}</Badge>
            <Badge v-if="$page.props.contadores?.porRegistrar" variant="outline" class="border-primary/30 text-primary">{{ $page.props.contadores.porRegistrar }} por registrar</Badge>
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

        <Card v-for="a in asignados" :key="'a' + a.id">
            <CardHeader>
                <CardDescription class="font-semibold">Te lo han encargado<span v-if="a.vence"> · para el {{ a.vence }}</span></CardDescription>
                <CardTitle class="text-base">{{ a.titulo }}<span v-if="a.residente"> · {{ a.residente }}</span></CardTitle>
            </CardHeader>
            <CardContent><Button variant="outline" class="h-11 rounded-full" @click="post(`/auxiliar/avisos/${a.id}/hecho`)">Hecho</Button></CardContent>
        </Card>

        <!-- 2 · Con novedades -->
        <section>
            <h2 class="mb-2 text-sm font-semibold">Con novedades <span class="font-normal text-muted-foreground">· toca para ver cómo atenderle</span></h2>
            <Card class="gap-0 overflow-hidden py-0">
                <div v-for="(n, i) in novedades" :key="n.id" :class="i && 'border-t'">
                    <FilaResidente :residente="n.residente" :texto="n.texto" :detalle="`${n.quien} · ${n.hora}`" :incidencia="n.incidencia" @abrir="ver(n.residente)" />
                </div>
                <p v-if="!novedades.length" class="p-4 text-sm text-muted-foreground">Sin novedades de las últimas 12 horas.</p>
            </Card>
        </section>

        <!-- 3 · Plegado -->
        <Collapsible v-if="vuelvenHoy.length" class="rounded-xl border bg-card">
            <CollapsibleTrigger class="flex min-h-12 w-full items-center justify-between px-4 font-semibold">Vuelven hoy · {{ vuelvenHoy.length }} <ChevronDown class="size-4" /></CollapsibleTrigger>
            <CollapsibleContent>
                <div v-for="v in vuelvenHoy" :key="v.id" class="flex items-center justify-between gap-3 border-t px-4 py-3">
                    <span>{{ v.residente }}</span>
                    <Button size="sm" variant="outline" class="rounded-full" @click="post(`/auxiliar/ausencias/${v.id}/vuelto`)">Ha vuelto</Button>
                </div>
            </CollapsibleContent>
        </Collapsible>

        <Collapsible class="rounded-xl border bg-card">
            <CollapsibleTrigger class="flex min-h-12 w-full items-center justify-between px-4 font-semibold">Notas de compañeras · {{ notas.length }} <ChevronDown class="size-4" /></CollapsibleTrigger>
            <CollapsibleContent>
                <div v-for="(n, i) in notas" :key="i" class="border-t px-4 py-3">
                    <p class="text-xs text-muted-foreground">{{ n.quien }} · cerró a las {{ n.hora }}</p>
                    <p class="whitespace-pre-line text-sm">{{ n.nota }}</p>
                </div>
                <p v-if="!notas.length" class="border-t px-4 py-3 text-sm text-muted-foreground">Nadie ha dejado nota.</p>
            </CollapsibleContent>
        </Collapsible>

        <Collapsible class="rounded-xl border bg-card">
            <CollapsibleTrigger class="flex min-h-12 w-full items-center justify-between px-4 font-semibold">Sin novedades · {{ sinNovedades.length }} <ChevronDown class="size-4" /></CollapsibleTrigger>
            <CollapsibleContent>
                <div v-for="r in sinNovedades" :key="r.id" class="border-t">
                    <FilaResidente :residente="r" :texto="r.ultimo?.texto" :detalle="r.ultimo ? `Último registro · ${r.ultimo.hora}` : 'Sin registros recientes'" @abrir="ver(r)" />
                </div>
            </CollapsibleContent>
        </Collapsible>
    </div>

    <ComoAtender v-model:open="abierta" :residente="ficha" />
</template>
