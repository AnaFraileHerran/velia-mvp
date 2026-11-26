<script setup lang="ts">
/**
 * Registrar: grabar (o escribir) → revisar → confirmar.
 * 26/11/2026 (comentarios de Pablo):
 *  · Escribir ya no es un campo abierto: es un formulario guiado por categorías con respuestas rápidas en chips y texto libre opcional.
 *  · 26/11/2026 (Ana, tarde): se entra con «No puedo grabar» y arriba hay un campo para escribirlo con sus palabras
 *    (Velia lo ordena igual que la voz). Los apartados quedan debajo como ayuda, no como obligación.
 *  · Revisar: lo principal arriba (todas las categorías en una lista: las detectadas, editables, y las que faltan, vacías);
 *    lo complementario debajo y «Confirmar» fijo abajo.
 *  · Sin casilla de incidencia: Velia propone «Avisar a dirección» con el motivo (aceptar, cambiar o descartar) o la auxiliar lo pide.
 *  · Sin «Sin novedad» uno a uno: se marca en bloque al final, en Mi turno.
 * Regla 3: si el micrófono o la red fallan, siempre hay «Escribir» y el texto se guarda en el dispositivo.
 * Regla 10: la IA propone y la persona confirma; lo que acepta, cambia o descarta queda anotado al guardar.
 */
import { computed, onMounted, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, BellRing, ChevronDown, Lightbulb, Mic, Pencil, Plus, Square, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardTitle } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { postJson } from '@/lib/http'

const props = defineProps<{ residente: any; fecha: string; grabarYa: boolean; borrador: any | null; categoriasPosibles: string[]; tiposIncidencia: string[]; rapidas: Record<string, string[]>; opciones?: any[] }>()

const paso = ref<'grabar' | 'escribir' | 'revisar'>(props.borrador ? 'revisar' : 'grabar')
const texto = ref(props.borrador?.texto ?? '')
const categorias = ref<Record<string, string>>({ ...(props.borrador?.categorias ?? {}) })
const incidencia = ref(!!props.borrador?.incidencia)
const tipo = ref<string | null>(props.borrador?.tipo ?? null)
const propuesta = ref<Record<string, string>>({})
const faltan = ref<string[]>([])
const faltanAceptadas = ref<string[]>([])
const faltanDescartadas = ref<string[]>([])
const error = ref<string | null>(null)
// Grabar primero y decir de quién después (v1.5): si se entra desde el micro de la barra, aún no hay residente elegido
const sinElegir = !props.residente.id
const elegido = ref<number | null>(props.residente.id ?? null)
const nombreElegido = computed(() => props.opciones?.find((o) => o.id === elegido.value)?.nombre ?? props.residente.nombre)
// Pistas mientras habla (v1.5, comentario de Pablo en A2-07): las categorías se encienden al nombrarlas. Primero con palabras clave.
const CLAVES: Record<string, RegExp> = {
    'Alimentación e hidratación': /(comid|comer|comi[oó]|desayun|cen[aó]|almorz|merend|beb|agua|zumo|hambre|pur[eé])/i,
    'Higiene': /(duch|aseo|asea|lav|pañal|higiene|ba[ñn]o)/i,
    'Movilidad': /(camin|andad|anda|silla|levant|postur|paseo|grúa|grua|encamad)/i,
    'Sujeción': /(sujec|barandill|cintur)/i,
    'Descanso': /(dorm|sueñ|siesta|descans|despert|noche)/i,
    'Ánimo': /(alegre|triste|tranquil|nervios|inquiet|[áa]nimo|contento|broma|llor|enfad)/i,
    'Incidencias': /(ca[íi]d|cay[óo]|herida|fiebre|v[óo]mit|golpe|mare[óo]|úlcera|ulcera)/i,
}
const contadas = computed(() => props.categoriasPosibles.filter((c) => CLAVES[c]?.test(parcial.value)))
const porContar = computed(() => props.categoriasPosibles.filter((c) => c !== 'Incidencias' && !contadas.value.includes(c)))
// Avisar a dirección: lo que propone Velia (si detecta algo) y lo que decide la auxiliar
const avisoPropuesto = ref<string | null>(null)
const avisoDecision = ref<'aceptada' | 'cambiada' | 'descartada' | null>(null)
const pedirAviso = ref(false)
const motivoAviso = ref('')
const ordenando = ref(false)
const guardando = ref(false)
const claveLocal = `velia-pendiente-${props.residente.id}`

// ---------- Dictado con la Web Speech API (si no hay, se pasa a escribir) ----------
const SR: any = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition
const grabando = ref(false)
const parcial = ref('')
const reloj = ref('00:00')
let rec: any = null, finalTxt = '', inicio = 0, t: any = null

function empezar(auto = false) {
    if (!SR) { error.value = 'En este dispositivo no se puede grabar. Escríbelo aquí.'; paso.value = 'escribir'; return }
    rec = new SR(); rec.lang = 'es-ES'; rec.continuous = true; rec.interimResults = true
    finalTxt = ''; parcial.value = ''
    rec.onresult = (e: any) => {
        let p = ''
        for (let i = e.resultIndex; i < e.results.length; i++) {
            if (e.results[i].isFinal) finalTxt += e.results[i][0].transcript + '. '
            else p += e.results[i][0].transcript
        }
        parcial.value = finalTxt + p
    }
    // Si el navegador no deja empezar sin un toque (desde el micro flotante), se queda el botón grande; si falla el micrófono, a escribir.
    rec.onerror = (e: any) => { grabando.value = false; clearInterval(t); if (!(auto && e.error === 'not-allowed')) { error.value = 'No se ha podido grabar. Escríbelo aquí.'; paso.value = 'escribir' } }
    try { rec.start() } catch { return }
    grabando.value = true; inicio = Date.now()
    t = setInterval(() => { const s = Math.floor((Date.now() - inicio) / 1000); reloj.value = `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}` }, 500)
}
function parar() {
    rec?.stop(); grabando.value = false; clearInterval(t)
    setTimeout(() => { texto.value = (finalTxt || parcial.value).trim(); ordenar() }, 400)
}

// ---------- Velia ordena ----------
async function ordenar() {
    error.value = null
    if (!texto.value.trim()) { error.value = 'No hemos recogido nada. Prueba otra vez o escribe.'; return }
    try { localStorage.setItem(claveLocal, texto.value) } catch {}
    ordenando.value = true
    try {
        const o = await postJson('/auxiliar/registrar/ordenar', { texto: texto.value, buscarResidente: sinElegir })
        if (sinElegir && o.detectado && !elegido.value) elegido.value = o.detectado
        categorias.value = { ...o.categorias }; propuesta.value = { ...o.categorias }
        incidencia.value = o.incidencia; tipo.value = o.tipo; faltan.value = o.faltan ?? []
        avisoPropuesto.value = o.incidencia ? (o.tipo || 'Otra') : null
        avisoDecision.value = o.incidencia ? 'aceptada' : null
        paso.value = 'revisar'
    } catch (e: any) {
        error.value = navigator.onLine ? e.message : 'Sin conexión: lo has escrito y está guardado en este dispositivo. Envíalo cuando vuelva la red.'
        paso.value = 'escribir'
    } finally { ordenando.value = false }
}

// ---------- Escribir: formulario guiado (chips + texto libre opcional) ----------
const guiado = ref<Record<string, { chips: string[]; libre: string }>>(
    Object.fromEntries(props.categoriasPosibles.map((c) => [c, { chips: [], libre: '' }])),
)
const abierta = ref<string | null>(props.categoriasPosibles[0] ?? null)
function alternarChip(c: string, chip: string) {
    const g = guiado.value[c]; const i = g.chips.indexOf(chip)
    i >= 0 ? g.chips.splice(i, 1) : g.chips.push(chip)
}
const textoGuiado = (c: string) => [guiado.value[c].chips.join(', '), guiado.value[c].libre.trim()].filter(Boolean).join('. ')
const hayGuiado = computed(() => props.categoriasPosibles.some((c) => textoGuiado(c)))
const hayEscrito = computed(() => hayGuiado.value || !!texto.value.trim())
// Revisar lo escrito: si solo ha marcado apartados, se usan tal cual; si ha escrito con sus palabras, Velia lo ordena (con los apartados añadidos).
function revisarEscrito() {
    if (!texto.value.trim()) return revisarGuiado()
    const extra = props.categoriasPosibles.map((c) => textoGuiado(c) && `${c}: ${textoGuiado(c)}.`).filter(Boolean).join(' ')
    if (extra) texto.value = `${texto.value.trim()} ${extra}`
    ordenar()
}
function revisarGuiado() {
    const cats: Record<string, string> = {}
    for (const c of props.categoriasPosibles) { const t = textoGuiado(c); if (t) cats[c] = t.endsWith('.') ? t : t + '.' }
    categorias.value = cats; propuesta.value = {}
    texto.value = Object.entries(cats).map(([c, t]) => `${c}: ${t}`).join(' ')
    // Si ha marcado algo en «Incidencias», se propone avisar a dirección; puede cambiarlo o descartarlo.
    incidencia.value = 'Incidencias' in cats; tipo.value = incidencia.value ? 'Otra' : null
    avisoPropuesto.value = null; avisoDecision.value = null
    paso.value = 'revisar'
}

// ---------- Revisar ----------
const vacias = computed(() => props.categoriasPosibles.filter((c) => !(c in categorias.value)))
function aceptarAviso() { incidencia.value = true; avisoDecision.value = 'aceptada' }
function descartarAviso() { incidencia.value = false; avisoDecision.value = 'descartada' }
function cambiarTipo(t: string) { tipo.value = t; incidencia.value = true; if (avisoPropuesto.value) avisoDecision.value = t === avisoPropuesto.value ? 'aceptada' : 'cambiada' }
const faltanVisibles = computed(() => faltan.value.filter((c) => !(c in categorias.value) && !faltanDescartadas.value.includes(c)))
function anadirSinNovedad(c: string) { categorias.value[c] = 'Sin novedad.'; faltanAceptadas.value.push(c) }
function descartarFaltan() { faltanDescartadas.value.push(...faltanVisibles.value) }
function anadir(c: string) { categorias.value[c] = '' }
function quitar(c: string) { delete categorias.value[c] }

function guardar(estado: 'confirmado' | 'borrador') {
    // Las categorías que se han dejado vacías no se guardan
    for (const c of Object.keys(categorias.value)) if (!String(categorias.value[c] ?? '').trim()) delete categorias.value[c]
    guardando.value = true
    if (!elegido.value) { error.value = 'Elige de quién es el registro.'; return }
    router.post(`/auxiliar/registrar/${elegido.value}`, {
        estado, texto: texto.value, categorias: categorias.value, incidencia: incidencia.value, tipo: tipo.value,
        propuesta: propuesta.value, registroId: props.borrador?.id ?? null,
        faltanAceptadas: faltanAceptadas.value, faltanDescartadas: faltanDescartadas.value,
        avisoPropuesto: avisoPropuesto.value, avisoDecision: avisoDecision.value, motivoAviso: motivoAviso.value,
    }, {
        onSuccess: () => { try { localStorage.removeItem(claveLocal); localStorage.removeItem(claveLocal + '-guiado') } catch {} },
        onFinish: () => (guardando.value = false),
    })
}

watch(texto, (v) => { if (paso.value === 'escribir') try { localStorage.setItem(claveLocal, v) } catch {} })
// El formulario guiado también se guarda en el dispositivo mientras se rellena (regla 3: si cae la red, no se pierde)
watch(guiado, (v) => { try { localStorage.setItem(claveLocal + '-guiado', JSON.stringify(v)) } catch {} }, { deep: true })
onMounted(() => {
    if (!texto.value) { try { texto.value = localStorage.getItem(claveLocal) ?? '' } catch {} }
    try { const g = JSON.parse(localStorage.getItem(claveLocal + '-guiado') ?? 'null'); if (g) Object.assign(guiado.value, g) } catch {}
    if (texto.value && paso.value === 'grabar') paso.value = 'escribir'
    else if (props.grabarYa && paso.value === 'grabar') empezar(true)
})
</script>

<template>
    <Head :title="residente.nombre" />
    <header class="mb-6 flex items-center gap-3">
        <Button as-child variant="ghost" size="icon-lg" class="rounded-full" :aria-label="sinElegir ? 'Volver a Mi planta' : 'Volver a la ficha'"><Link :href="sinElegir ? '/auxiliar' : `/auxiliar/residente/${residente.id}`"><ArrowLeft class="size-5" /></Link></Button>
        <Avatar v-if="!sinElegir" class="size-12"><AvatarImage :src="residente.foto" alt="" /><AvatarFallback>{{ residente.nombre[0] }}</AvatarFallback></Avatar>
        <div class="min-w-0">
            <h1 class="text-xl font-semibold leading-tight md:text-2xl">{{ paso === 'revisar' ? (sinElegir ? 'Revisa el registro' : `Revisa el registro de ${residente.corto}`) : (sinElegir ? 'Habla: luego eliges de quién es' : residente.nombre) }}</h1>
            <p class="text-sm text-muted-foreground"><template v-if="!sinElegir">Hab. {{ residente.habitacion }} · </template>{{ fecha }}</p>
        </div>
    </header>

    <!-- Grabar: un botón enorme -->
    <section v-if="paso === 'grabar'" class="flex flex-col items-center gap-6 py-6 text-center">
        <button
            type="button"
            class="flex size-40 items-center justify-center rounded-full text-primary-foreground shadow-lg transition"
            :class="grabando ? 'animate-pulse bg-destructive' : 'bg-primary'"
            :aria-label="grabando ? 'Parar y revisar' : 'Grabar'"
            @click="grabando ? parar() : empezar()"
        >
            <Square v-if="grabando" class="size-12" /><Mic v-else class="size-16" />
        </button>
        <p class="text-lg font-semibold">{{ grabando ? `Grabando · ${reloj} · toca para terminar` : ordenando ? 'Velia lo está ordenando…' : 'Toca para grabar' }}</p>
        <p class="max-w-sm text-muted-foreground">Cuenta cómo ha comido, la higiene, cómo se ha movido, si ha descansado y cómo está de ánimo. Velia lo ordena y tú lo confirmas.</p>
        <!-- Pistas: qué contar y qué ha recogido ya Velia mientras hablas -->
        <div class="w-full max-w-md space-y-2" aria-live="polite">
            <div class="flex flex-wrap justify-center gap-2">
                <span v-for="c in categoriasPosibles.filter((x) => x !== 'Incidencias')" :key="c"
                    class="rounded-full border px-3 py-1 text-sm transition"
                    :class="contadas.includes(c) ? 'border-success bg-success text-success-foreground' : 'bg-card text-muted-foreground'">
                    {{ contadas.includes(c) ? '✓ ' : '' }}{{ c === 'Alimentación e hidratación' ? 'Alimentación' : c }}
                </span>
            </div>
            <p v-if="grabando && porContar.length" class="text-sm text-muted-foreground">Aún no has contado: {{ porContar.map((c) => c === 'Alimentación e hidratación' ? 'alimentación' : c.toLowerCase()).join(', ') }}</p>
        </div>
        <p v-if="parcial" class="max-w-md text-sm italic text-muted-foreground">{{ parcial }}</p>
        <p v-if="error" role="alert" class="text-sm font-medium text-destructive">{{ error }}</p>
        <Button variant="outline" size="lg" class="h-12 rounded-full px-6 text-base" @click="paso = 'escribir'"><Pencil /> No puedo grabar: escribir</Button>
    </section>

    <!-- Escribir: formulario guiado por categorías (comentario de Pablo en A2-08) -->
    <section v-else-if="paso === 'escribir'" class="space-y-3 pb-24">
        <p v-if="error" role="alert" class="text-sm font-medium text-destructive">{{ error }}</p>
        <!-- Escribir con sus palabras (Ana, 26/11): lo mismo que diría hablando; Velia lo ordena por categorías -->
        <Card class="gap-3 px-5 py-4">
            <label for="escrito" class="font-semibold">Escríbelo con tus palabras</label>
            <Textarea id="escrito" v-model="texto" rows="4" class="text-base" placeholder="Ej.: ha desayunado todo, se ha duchado con ayuda y está de buen humor" />
            <p class="text-sm text-muted-foreground">Velia lo ordena y tú lo revisas. Se guarda en este dispositivo mientras escribes.</p>
        </Card>
        <p class="pt-2 text-sm font-medium">O toca lo que corresponda en cada apartado (opcional):</p>
        <Card v-for="c in categoriasPosibles" :key="c" class="gap-0 py-0" :class="textoGuiado(c) && 'border-primary/40'">
            <button type="button" class="flex min-h-14 w-full items-center justify-between gap-3 px-5 text-left" :aria-expanded="abierta === c" @click="abierta = abierta === c ? null : c">
                <span class="min-w-0">
                    <span class="block font-semibold">{{ c }}</span>
                    <span class="block truncate text-sm" :class="textoGuiado(c) ? 'text-foreground/80' : 'text-muted-foreground'">{{ textoGuiado(c) || 'Sin rellenar' }}</span>
                </span>
                <ChevronDown class="size-5 shrink-0 text-muted-foreground transition" :class="abierta === c && 'rotate-180'" />
            </button>
            <div v-if="abierta === c" class="space-y-3 border-t px-5 py-4">
                <div class="flex flex-wrap gap-2">
                    <button v-for="chip in rapidas[c] ?? []" :key="chip" type="button"
                        class="min-h-11 rounded-full border px-4 text-sm font-medium transition"
                        :class="guiado[c].chips.includes(chip) ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:bg-accent'"
                        :aria-pressed="guiado[c].chips.includes(chip)" @click="alternarChip(c, chip)">{{ chip }}</button>
                </div>
                <Textarea v-model="guiado[c].libre" rows="2" class="text-base" :placeholder="`Algo más sobre ${c.toLowerCase()} (opcional)`" />
            </div>
        </Card>
        <div class="fixed inset-x-0 z-20 px-4" style="bottom: calc(4.75rem + env(safe-area-inset-bottom))">
            <div class="mx-auto flex max-w-3xl gap-2">
                <Button size="lg" class="h-14 flex-1 rounded-full text-base shadow-lg" :disabled="!hayEscrito || ordenando" @click="revisarEscrito">{{ ordenando ? 'Velia lo está ordenando…' : 'Revisar' }}</Button>
                <Button variant="outline" size="lg" class="h-14 rounded-full bg-card px-5 text-base shadow-lg" aria-label="Grabar en vez de escribir" @click="paso = 'grabar'"><Mic /></Button>
            </div>
        </div>
    </section>

    <!-- Revisar: primero lo detectado y lo que falta, en una lista; luego lo complementario; «Confirmar» fijo abajo -->
    <section v-else class="space-y-4 pb-24">
        <!-- ¿De quién? (solo si se empezó a grabar sin elegir): Velia propone si ha oído el nombre o la habitación -->
        <Card v-if="sinElegir" class="gap-3 py-4" :class="!elegido && 'border-primary/50'">
            <CardContent class="space-y-3">
                <p class="font-semibold">{{ elegido ? `Es de ${nombreElegido}` : '¿De quién es este registro?' }}
                    <span v-if="elegido" class="text-sm font-normal text-muted-foreground">· cámbialo si no es así</span></p>
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button v-for="o in opciones ?? []" :key="o.id" type="button" class="flex min-h-12 shrink-0 items-center gap-2 rounded-full border px-3 text-sm"
                        :class="elegido === o.id ? 'border-primary bg-primary text-primary-foreground' : 'bg-card'" :aria-pressed="elegido === o.id" @click="elegido = o.id">
                        <Avatar class="size-8"><AvatarImage :src="o.foto" alt="" /><AvatarFallback>{{ o.nombre[0] }}</AvatarFallback></Avatar>
                        {{ o.nombre.split(' ')[0] }} · {{ o.habitacion }}
                    </button>
                </div>
            </CardContent>
        </Card>
        <p v-if="error" role="alert" class="text-sm font-medium text-destructive">{{ error }}</p>
        <div class="space-y-3">
            <Card v-for="(v, c) in categorias" :key="c" class="gap-2 py-4">
                <div class="flex items-center justify-between gap-2 px-6">
                    <CardTitle class="text-base">{{ c }}</CardTitle>
                    <Button variant="ghost" size="icon" class="rounded-full text-muted-foreground" :aria-label="`Quitar ${c}`" @click="quitar(String(c))"><X /></Button>
                </div>
                <CardContent><Textarea v-model="categorias[c]" rows="2" class="text-base" /></CardContent>
            </Card>
            <!-- Lo que falta, en la misma lista y vacío: se rellena aquí mismo -->
            <button v-for="c in vacias" :key="'v' + c" type="button" class="flex min-h-14 w-full items-center justify-between gap-3 rounded-xl border border-dashed bg-card px-6 text-left" @click="anadir(c)">
                <span><span class="block font-semibold text-foreground/80">{{ c }}</span><span class="block text-sm text-muted-foreground">No lo has contado · toca para añadir</span></span>
                <Plus class="size-5 text-muted-foreground" />
            </button>
        </div>

        <!-- Avisar a dirección: lo propone Velia (regla 10) o lo pide la auxiliar con un motivo -->
        <Card v-if="avisoPropuesto && avisoDecision !== 'descartada'" class="gap-3 border-destructive/40 py-4">
            <CardContent class="space-y-3">
                <p class="flex items-center gap-2 text-sm font-semibold text-destructive"><BellRing class="size-4" /> Velia propone avisar a dirección: posible {{ (tipo || avisoPropuesto).toLowerCase() }}</p>
                <div class="flex flex-wrap gap-2">
                    <button v-for="ti in tiposIncidencia" :key="ti" type="button" class="min-h-10 rounded-full border px-3 text-sm" :class="tipo === ti ? 'border-destructive bg-destructive text-white' : 'bg-card'" @click="cambiarTipo(ti)">{{ ti }}</button>
                </div>
                <Textarea v-model="motivoAviso" rows="2" class="text-base" placeholder="Motivo en pocas palabras (opcional)" />
                <div class="flex flex-wrap gap-2">
                    <Button size="sm" variant="ghost" class="rounded-full" @click="descartarAviso">No avisar</Button>
                </div>
            </CardContent>
        </Card>
        <Card v-else-if="incidencia || pedirAviso" class="gap-3 border-destructive/40 py-4">
            <CardContent class="space-y-3">
                <p class="flex items-center gap-2 text-sm font-semibold text-destructive"><BellRing class="size-4" /> Avisar a dirección</p>
                <div class="flex flex-wrap gap-2">
                    <button v-for="ti in tiposIncidencia" :key="ti" type="button" class="min-h-10 rounded-full border px-3 text-sm" :class="tipo === ti ? 'border-destructive bg-destructive text-white' : 'bg-card'" @click="cambiarTipo(ti)">{{ ti }}</button>
                </div>
                <Textarea v-model="motivoAviso" rows="2" class="text-base" placeholder="¿Por qué? En pocas palabras" />
                <Button size="sm" variant="ghost" class="rounded-full" @click="incidencia = false; pedirAviso = false; tipo = null">Quitar el aviso</Button>
            </CardContent>
        </Card>
        <Button v-else variant="link" class="px-0 text-base text-destructive" @click="pedirAviso = true"><BellRing /> Avisar a dirección</Button>

        <Card v-if="faltanVisibles.length" class="gap-3 border-primary/30 bg-secondary py-4">
            <CardContent class="space-y-3">
                <p class="flex items-center gap-2 text-sm font-semibold text-primary"><Lightbulb class="size-4" /> Velia te sugiere <Badge variant="outline" class="border-primary/30 text-primary">Ejemplo</Badge></p>
                <p class="text-sm">En este turno se suele anotar también: <strong>{{ faltanVisibles.join(', ') }}</strong>. ¿Lo marcas como «Sin novedad»?</p>
                <div class="flex flex-wrap gap-2">
                    <Button v-for="c in faltanVisibles" :key="c" size="sm" variant="outline" class="rounded-full bg-card" @click="anadirSinNovedad(c)"><Plus /> {{ c }}</Button>
                    <Button size="sm" variant="ghost" class="rounded-full" @click="descartarFaltan">No, gracias</Button>
                </div>
            </CardContent>
        </Card>

        <Collapsible v-if="texto" class="rounded-xl border bg-card">
            <CollapsibleTrigger class="flex min-h-12 w-full items-center justify-between px-4 text-sm font-semibold">Lo que has contado <ChevronDown class="size-4" /></CollapsibleTrigger>
            <CollapsibleContent><p class="border-t px-4 py-3 text-sm text-muted-foreground">{{ texto }}</p></CollapsibleContent>
        </Collapsible>

        <div class="flex flex-wrap gap-2">
            <Button variant="ghost" class="rounded-full" @click="paso = 'grabar'"><Mic /> Volver a grabar</Button>
            <Button variant="ghost" class="rounded-full" :disabled="guardando" @click="guardar('borrador')">Guardar borrador</Button>
        </div>

        <div class="fixed inset-x-0 z-20 px-4" style="bottom: calc(4.75rem + env(safe-area-inset-bottom))">
            <div class="mx-auto max-w-3xl">
                <Button size="lg" class="h-14 w-full rounded-full text-base shadow-lg" :disabled="guardando || !Object.keys(categorias).length || !elegido" @click="guardar('confirmado')">
                    {{ incidencia ? 'Confirmar y avisar a dirección' : 'Confirmar' }}
                </Button>
            </div>
        </div>
    </section>
</template>
