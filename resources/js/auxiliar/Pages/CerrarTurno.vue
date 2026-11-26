<script setup lang="ts">
/**
 * Antes de irte (opcional, 26/11/2026): ya no hace falta cerrar el turno para irse. Aquí se deja el relevo:
 * quien no tiene registro se marca sin novedad de una vez o, si no se ha podido atender, se dice el motivo (va a la dirección);
 * el resumen para el siguiente turno se hace solo con lo confirmado y se puede añadir una nota.
 */
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, CheckCheck, X } from '@lucide/vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

const props = defineProps<{ pendientes: any[]; relevo: any[]; motivos: Record<string, string> }>()
const form = useForm({ motivo: null as string | null, motivoTexto: '', nota: '', quitados: [] as number[] })
const visibles = computed(() => props.relevo.filter((r) => !form.quitados.includes(r.id)))
const cerrar = () => form.post('/auxiliar/antes-de-irte')
const sinRegistro = computed(() => props.pendientes.filter((p) => p.estado === 'pendiente'))
const marcar = () => router.post('/auxiliar/sin-novedad', { ids: sinRegistro.value.map((p) => p.id) }, { preserveScroll: true })
</script>

<template>
    <Head title="Antes de irte" />
    <header class="mb-5 flex items-center gap-3">
        <Button as-child variant="ghost" size="icon-lg" class="rounded-full" aria-label="Volver a Mi turno"><Link href="/auxiliar/mi-turno"><ArrowLeft class="size-5" /></Link></Button>
        <div><h1 class="text-2xl font-semibold">Antes de irte</h1><p class="text-sm text-muted-foreground">Opcional. Tu turno termina solo al acabar tu horario.</p></div>
    </header>

    <div class="space-y-4">
        <Card v-if="pendientes.length" class="border-warning/50">
            <CardHeader>
                <CardTitle class="text-base">Quedan {{ pendientes.length }} sin registrar</CardTitle>
                <CardDescription>{{ pendientes.map((p) => p.corto).join(', ') }}. Si no ha pasado nada, márcalos sin novedad. Si no has podido atenderles, di el motivo: la dirección lo verá.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <Button v-if="sinRegistro.length" class="h-12 w-full rounded-full text-base" @click="marcar"><CheckCheck /> Marcar {{ sinRegistro.length === 1 ? 'sin novedad' : `los ${sinRegistro.length} sin novedad` }}</Button>
                <p class="pt-1 text-sm font-medium">O, si no has podido atenderles:</p>
                <RadioGroup v-model="form.motivo" class="grid gap-2 sm:grid-cols-2">
                    <div v-for="(t, k) in motivos" :key="k" class="flex min-h-11 items-center gap-3 rounded-lg border px-3">
                        <RadioGroupItem :id="`m-${k}`" :value="String(k)" />
                        <Label :for="`m-${k}`" class="flex-1 text-base font-normal">{{ t }}</Label>
                    </div>
                </RadioGroup>
                <Textarea v-if="form.motivo === 'otros'" v-model="form.motivoTexto" rows="2" placeholder="Cuéntalo en pocas palabras" class="text-base" />
                <p v-if="form.errors.motivo || form.errors.motivoTexto" role="alert" class="text-sm font-medium text-destructive">{{ form.errors.motivo || form.errors.motivoTexto }}</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Resumen para el siguiente turno</CardTitle>
                <CardDescription>Se hace solo con lo que has confirmado. Quita lo que no haga falta.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-2">
                <div v-for="r in visibles" :key="r.id" class="flex items-start gap-2 rounded-lg border px-3 py-2">
                    <span class="flex-1 text-sm"><strong>{{ r.residente }}</strong> · {{ r.texto }} <Badge v-if="r.incidencia" variant="destructive" class="ml-1">Incidencia</Badge></span>
                    <Button variant="ghost" size="icon-sm" class="rounded-full" :aria-label="`Quitar a ${r.residente} del resumen`" @click="form.quitados.push(r.id)"><X /></Button>
                </div>
                <p v-if="!visibles.length" class="text-sm text-muted-foreground">Nada confirmado en este turno.</p>
                <Label for="nota" class="pt-2">Nota para quien entra (opcional)</Label>
                <Textarea id="nota" v-model="form.nota" rows="3" class="text-base" placeholder="Ej.: la cama de la 104 hace ruido, avisado a mantenimiento" />
            </CardContent>
        </Card>

        <Button size="lg" class="h-12 w-full rounded-full text-base" :disabled="form.processing" @click="cerrar">Dejar el relevo y terminar mi turno</Button>
    </div>
</template>
