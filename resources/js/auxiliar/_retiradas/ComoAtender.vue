<script setup lang="ts">
/**
 * «Cómo atender» (solo lectura, la mantiene la dirección). Lo que puede hacer daño va primero: alergias y riesgos.
 * Icono junto a cada dato (comentario de Antonio en A1-04). Desde aquí se pasa a registrar.
 */
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Accessibility, MessageCircle, Mic, Phone, ShieldAlert, Utensils } from '@lucide/vue'
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'

const props = defineProps<{ residente: any | null }>()
const abierta = defineModel<boolean>('open', { default: false })
const a = computed(() => props.residente?.atender ?? {})
const datos = computed(() => [
    { t: 'Movilidad', v: a.value.movilidad, icono: Accessibility },
    { t: 'Sujeción', v: a.value.sujecion, icono: ShieldAlert },
    { t: 'Dieta y textura', v: a.value.dieta, icono: Utensils },
    { t: 'Cómo tratarle', v: a.value.comoTratarle, icono: MessageCircle },
])
</script>

<template>
    <Sheet v-model:open="abierta">
        <SheetContent v-if="residente" side="right" class="w-full gap-0 sm:max-w-md">
            <SheetHeader class="border-b">
                <SheetTitle>Cómo atender a {{ residente.corto }}</SheetTitle>
                <SheetDescription>Hab. {{ residente.habitacion }} · solo lectura · la mantiene la dirección</SheetDescription>
            </SheetHeader>

            <div class="flex-1 space-y-5 overflow-y-auto p-4">
                <div class="flex items-center gap-3">
                    <Avatar class="size-18"><AvatarImage :src="residente.foto" :alt="`Foto de ${residente.nombre}`" /><AvatarFallback>{{ residente.nombre[0] }}</AvatarFallback></Avatar>
                    <div>
                        <p class="text-lg font-semibold">{{ residente.nombre }}</p>
                        <p class="text-sm text-muted-foreground">{{ residente.planta }} · Hab. {{ residente.habitacion }}</p>
                    </div>
                </div>

                <Alert v-if="a.alergias || a.riesgos" variant="destructive">
                    <AlertDescription class="font-semibold text-destructive">
                        <span v-if="a.alergias">Alergia: {{ a.alergias }}</span>
                        <span v-if="a.alergias && a.riesgos"> · </span>
                        <span v-if="a.riesgos">Riesgo: {{ a.riesgos }}</span>
                    </AlertDescription>
                </Alert>

                <dl class="space-y-4">
                    <div v-for="d in datos" :key="d.t" class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-foreground/80"><component :is="d.icono" class="size-5" aria-hidden="true" /></span>
                        <div>
                            <dt class="font-semibold">{{ d.t }}</dt>
                            <dd class="text-muted-foreground">{{ d.v || '—' }}</dd>
                        </div>
                    </div>
                    <div v-if="a.contacto" class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-foreground/80"><Phone class="size-5" aria-hidden="true" /></span>
                        <div>
                            <dt class="font-semibold">Contacto de emergencia</dt>
                            <dd><a :href="`tel:${a.contacto.telefono?.replace(/\s+/g, '')}`" class="text-primary underline underline-offset-2">{{ a.contacto.nombre }} ({{ a.contacto.relacion }}) · {{ a.contacto.telefono }}</a></dd>
                        </div>
                    </div>
                </dl>
            </div>

            <SheetFooter class="flex-row gap-2 border-t">
                <Button as-child size="lg" class="h-12 flex-1 rounded-full text-base">
                    <Link :href="`/auxiliar/registrar/${residente.id}`"><Mic aria-hidden="true" /> Registrar</Link>
                </Button>
                <Button variant="outline" size="lg" class="h-12 rounded-full text-base" @click="abierta = false">Cerrar</Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
