<script setup lang="ts">
/**
 * Registrar: grabar (o escribir) → Velia ordena por categorías → la auxiliar revisa y confirma.
 * Regla 3: si el micrófono o la red fallan, siempre hay «Escribir» y el texto se guarda en el dispositivo.
 * Regla 10: la IA propone y la persona confirma; lo que acepta, cambia o descarta queda anotado al guardar.
 */
import { computed, onMounted, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Lightbulb, Mic, Pencil, Plus, Square, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardTitle } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { postJson } from '@/lib/http'

const props = defineProps<{ residente: any; fecha: string; grabarYa: boolean; borrador: any | null; categoriasPosibles: string[]; tiposIncidencia: string[] }>()

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
    if (!SR) { paso.value = 'escribir'; return }
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
    rec.onerror = (e: any) => { grabando.value = false; clearInterval(t); if (!(auto && e.error === 'not-allowed')) paso.value = 'escribir' }
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
        const o = await postJson('/auxiliar/registrar/ordenar', { texto: texto.value })
        categorias.value = { ...o.categorias }; propuesta.value = { ...o.categorias }
        incidencia.value = o.incidencia; tipo.value = o.tipo; faltan.value = o.faltan ?? []
        paso.value = 'revisar'
    } catch (e: any) {
        error.value = navigator.onLine ? e.message : 'Sin conexión: lo has escrito y está guardado en este dispositivo. Envíalo cuando vuelva la red.'
        paso.value = 'escribir'
    } finally { ordenando.value = false }
}

// ---------- Revisar ----------
const sinUsar = computed(() => props.categoriasPosibles.filter((c) => !(c in categorias.value)))
const faltanVisibles = computed(() => faltan.value.filter((c) => !(c in categorias.value) && !faltanDescartadas.value.includes(c)))
function anadirSinNovedad(c: string) { categorias.value[c] = 'Sin novedad.'; faltanAceptadas.value.push(c) }
function descartarFaltan() { faltanDescartadas.value.push(...faltanVisibles.value) }
function anadir(c: string) { categorias.value[c] = '' }
function quitar(c: string) { delete categorias.value[c] }

function guardar(estado: 'confirmado' | 'borrador' | 'sin_novedad') {
    guardando.value = true
    router.post(`/auxiliar/registrar/${props.residente.id}`, {
        estado, texto: texto.value, categorias: categorias.value, incidencia: incidencia.value, tipo: tipo.value,
        propuesta: propuesta.value, registroId: props.borrador?.id ?? null,
        faltanAceptadas: faltanAceptadas.value, faltanDescartadas: faltanDescartadas.value,
    }, {
        onSuccess: () => { try { localStorage.removeItem(claveLocal) } catch {} },
        onFinish: () => (guardando.value = false),
    })
}

watch(texto, (v) => { if (paso.value === 'escribir') try { localStorage.setItem(claveLocal, v) } catch {} })
onMounted(() => {
    if (!texto.value) { try { texto.value = localStorage.getItem(claveLocal) ?? '' } catch {} }
    if (texto.value && paso.value === 'grabar') paso.value = 'escribir'
    else if (props.grabarYa && paso.value === 'grabar') empezar(true)
})
</script>

<template>
    <Head :title="residente.nombre" />
    <header class="mb-6 flex items-center gap-3">
        <Button as-child variant="ghost" size="icon-lg" class="rounded-full" aria-label="Volver a Residentes"><Link href="/auxiliar/residentes"><ArrowLeft class="size-5" /></Link></Button>
        <Avatar class="size-12"><AvatarImage :src="residente.foto" alt="" /><AvatarFallback>{{ residente.nombre[0] }}</AvatarFallback></Avatar>
        <div class="min-w-0">
            <h1 class="text-xl font-semibold leading-tight md:text-2xl">{{ paso === 'revisar' ? `Revisa el registro de ${residente.corto}` : residente.nombre }}</h1>
            <p class="text-sm text-muted-foreground">Hab. {{ residente.habitacion }} · {{ fecha }}</p>
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
        <p class="max-w-sm text-muted-foreground">Habla con naturalidad. Velia lo ordena y tú lo confirmas.</p>
        <p v-if="parcial" class="max-w-md text-sm italic text-muted-foreground">{{ parcial }}</p>
        <p v-if="error" role="alert" class="text-sm font-medium text-destructive">{{ error }}</p>
        <div class="flex flex-col items-center gap-3">
            <Button variant="outline" size="lg" class="h-12 rounded-full px-6 text-base" @click="paso = 'escribir'"><Pencil /> Escribir</Button>
            <Button variant="link" class="text-base" :disabled="guardando" @click="guardar('sin_novedad')">¿Turno tranquilo? Marca «Sin novedad»</Button>
        </div>
    </section>

    <!-- Escribir: si falla el micro o la red -->
    <section v-else-if="paso === 'escribir'" class="space-y-4">
        <Card class="border-warning/40 bg-[color-mix(in_oklch,var(--warning)_8%,white)]">
            <CardContent class="text-sm">
                <p class="font-semibold">Escribe el registro</p>
                <p>Si el micrófono no funciona o no hay cobertura, escríbelo aquí. Se guarda en este dispositivo mientras escribes y Velia lo ordena igual.</p>
            </CardContent>
        </Card>
        <Textarea v-model="texto" rows="7" class="bg-card text-base" placeholder="Escribe con naturalidad…" autofocus />
        <p v-if="error" role="alert" class="text-sm font-medium text-destructive">{{ error }}</p>
        <div class="flex flex-wrap gap-3">
            <Button size="lg" class="h-12 rounded-full px-6 text-base" :disabled="ordenando || !texto.trim()" @click="ordenar">{{ ordenando ? 'Ordenando…' : 'Ordenar con Velia' }}</Button>
            <Button variant="outline" size="lg" class="h-12 rounded-full px-6 text-base" @click="paso = 'grabar'"><Mic /> Grabar</Button>
        </div>
    </section>

    <!-- Revisar: categorías editables, incidencia y lo que suele faltar -->
    <section v-else class="space-y-4">
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

        <Card v-for="(v, c) in categorias" :key="c" class="gap-2 py-4">
            <div class="flex items-center justify-between gap-2 px-6">
                <CardTitle class="text-base">{{ c }}</CardTitle>
                <Button variant="ghost" size="icon" class="rounded-full text-muted-foreground" :aria-label="`Quitar ${c}`" @click="quitar(String(c))"><X /></Button>
            </div>
            <CardContent><Textarea v-model="categorias[c]" rows="2" class="text-base" /></CardContent>
        </Card>

        <div v-if="sinUsar.length" class="flex flex-wrap gap-2">
            <span class="self-center text-sm text-muted-foreground">Añadir:</span>
            <Button v-for="c in sinUsar" :key="c" size="sm" variant="secondary" class="rounded-full" @click="anadir(c)"><Plus /> {{ c }}</Button>
        </div>

        <Card class="gap-3 py-4" :class="incidencia && 'border-destructive/40'">
            <CardContent class="space-y-3">
                <label class="flex min-h-11 items-center gap-3 font-semibold">
                    <input v-model="incidencia" type="checkbox" class="size-5 accent-[var(--destructive)]" />
                    Hay una incidencia (la dirección recibe un aviso)
                </label>
                <RadioGroup v-if="incidencia" v-model="tipo" class="grid gap-2 sm:grid-cols-2">
                    <div v-for="ti in tiposIncidencia" :key="ti" class="flex min-h-11 items-center gap-3 rounded-lg border px-3">
                        <RadioGroupItem :id="`t-${ti}`" :value="ti" />
                        <Label :for="`t-${ti}`" class="flex-1 text-base font-normal">{{ ti }}</Label>
                    </div>
                </RadioGroup>
            </CardContent>
        </Card>

        <div class="flex flex-wrap gap-3 pt-2">
            <Button size="lg" class="h-12 flex-1 rounded-full text-base" :disabled="guardando || !Object.keys(categorias).length" @click="guardar('confirmado')">Confirmar</Button>
            <Button variant="outline" size="lg" class="h-12 rounded-full px-5 text-base" :disabled="guardando" @click="guardar('borrador')">Guardar borrador</Button>
            <Button variant="ghost" size="lg" class="h-12 rounded-full px-5 text-base" @click="paso = 'grabar'"><Mic /> Volver a grabar</Button>
        </div>
    </section>
</template>
