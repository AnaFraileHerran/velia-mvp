<script setup lang="ts">
/**
 * Ficha del residente como página (comentario de Pablo en A1-04: en el móvil, mejor una página que una ventana).
 * Lo que puede hacer daño primero (alergias y riesgos), «Cómo atender» con icono junto a cada dato (Antonio),
 * los 3 últimos registros y «Registrar» fijo abajo, al alcance del pulgar.
 */
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Accessibility, ArrowLeft, MessageCircle, Mic, Phone, ShieldAlert, Utensils } from '@lucide/vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const props = defineProps<{ residente: any; ultimos: any[] }>()
const a = computed(() => props.residente.atender ?? {})
const datos = computed(() => [
    { t: 'Movilidad', v: a.value.movilidad, icono: Accessibility },
    { t: 'Sujeción', v: a.value.sujecion, icono: ShieldAlert },
    { t: 'Dieta y textura', v: a.value.dieta, icono: Utensils },
    { t: 'Cómo tratarle', v: a.value.comoTratarle, icono: MessageCircle },
])
const estado: Record<string, string> = { pendiente: 'Por registrar en este turno', borrador: 'Tienes un borrador sin confirmar', registrado: 'Registrado en este turno', fuera: 'Fuera de la residencia' }
</script>

<template>
    <Head :title="residente.nombre" />
    <header class="mb-5 flex items-center gap-3">
        <Button as-child variant="ghost" size="icon-lg" class="rounded-full" aria-label="Volver a Mi planta"><Link href="/auxiliar"><ArrowLeft class="size-5" /></Link></Button>
        <Avatar class="size-14"><AvatarImage :src="residente.foto" :alt="`Foto de ${residente.nombre}`" /><AvatarFallback>{{ residente.nombre[0] }}</AvatarFallback></Avatar>
        <div class="min-w-0">
            <h1 class="text-xl font-semibold leading-tight md:text-2xl">{{ residente.nombre }}</h1>
            <p class="text-sm text-muted-foreground">{{ residente.planta }} · Hab. {{ residente.habitacion }}</p>
            <Badge :variant="residente.estado === 'registrado' ? 'default' : 'secondary'" :class="residente.estado === 'registrado' && 'bg-success text-success-foreground'" class="mt-1">{{ estado[residente.estado] ?? '' }}</Badge>
        </div>
    </header>

    <div class="space-y-4">
        <Alert v-if="a.alergias || a.riesgos" variant="destructive">
            <AlertDescription class="font-semibold text-destructive">
                <span v-if="a.alergias">Alergia: {{ a.alergias }}</span>
                <span v-if="a.alergias && a.riesgos"> · </span>
                <span v-if="a.riesgos">Riesgo: {{ a.riesgos }}</span>
            </AlertDescription>
        </Alert>

        <Card>
            <CardHeader><CardTitle class="text-base">Cómo atenderle</CardTitle></CardHeader>
            <CardContent>
                <dl class="grid gap-4 md:grid-cols-2">
                    <div v-for="d in datos" :key="d.t" class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-foreground/80"><component :is="d.icono" class="size-5" aria-hidden="true" /></span>
                        <div><dt class="font-semibold">{{ d.t }}</dt><dd class="text-muted-foreground">{{ d.v || '—' }}</dd></div>
                    </div>
                    <div v-if="a.contacto" class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-foreground/80"><Phone class="size-5" aria-hidden="true" /></span>
                        <div>
                            <dt class="font-semibold">Contacto de emergencia</dt>
                            <dd><a :href="`tel:${a.contacto.telefono?.replace(/\s+/g, '')}`" class="text-primary underline underline-offset-2">{{ a.contacto.nombre }} ({{ a.contacto.relacion }}) · {{ a.contacto.telefono }}</a></dd>
                        </div>
                    </div>
                </dl>
                <p class="mt-4 text-xs text-muted-foreground">Solo lectura. Si algo no es correcto, díselo a la dirección: lo corrige con motivo.</p>
            </CardContent>
        </Card>

        <Card class="gap-0 py-0">
            <CardHeader class="py-4"><CardTitle class="text-base">Últimos registros</CardTitle></CardHeader>
            <div v-for="r in ultimos" :key="r.id" class="border-t px-6 py-3">
                <p class="text-xs text-muted-foreground">{{ r.cuando }} · {{ r.quien }} <Badge v-if="r.incidencia" variant="destructive" class="ml-1">Incidencia</Badge></p>
                <p class="text-sm">{{ r.texto }}</p>
            </div>
            <p v-if="!ultimos.length" class="border-t px-6 py-3 text-sm text-muted-foreground">Aún no hay registros.</p>
        </Card>
    </div>

    <!-- «Registrar» fijo abajo, encima de la barra de navegación -->
    <div v-if="residente.estado !== 'fuera'" class="fixed inset-x-0 z-20 px-4" style="bottom: calc(4.75rem + env(safe-area-inset-bottom))">
        <div class="mx-auto max-w-3xl">
            <Button as-child size="lg" class="h-14 w-full rounded-full text-base shadow-lg">
                <Link :href="`/auxiliar/registrar/${residente.id}?grabar=1`"><Mic aria-hidden="true" /> {{ residente.estado === 'borrador' ? 'Terminar el registro' : 'Registrar' }}</Link>
            </Button>
        </div>
    </div>
</template>
