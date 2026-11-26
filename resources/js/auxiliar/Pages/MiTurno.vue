<script setup lang="ts">
/**
 * Mi turno: cómo va, «sin novedad» en bloque, quién trabaja hoy y salir.
 * 26/11/2026 (decisiones 2 y 3): ya no hay que cerrar el turno; termina solo al acabar el horario. «Salir» cambia de persona
 * en la tablet compartida (Antonio). Al final, quien no tiene registro se marca «sin novedad» de una vez, con una confirmación
 * (es la evidencia de que se le ha atendido: regla 4 y UNE). La nota para el relevo es opcional.
 */
import { ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { CheckCheck, LogOut, StickyNote, Users } from '@lucide/vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

const props = defineProps<{ registrados: number; total: number; borradores: number; pendientes: number; fuera: number; finalDeTurno: boolean; sinRegistro: any[]; companeras: any[]; equipo: any[] }>()
const yo = usePage<any>().props.yo
const pct = props.total ? Math.round((props.registrados / props.total) * 100) : 0
const confirmando = ref(false)
const verGuia = () => window.dispatchEvent(new Event('velia-guia'))
const marcar = () => router.post('/auxiliar/sin-novedad', { ids: props.sinRegistro.map((r) => r.id) }, { preserveScroll: true, onFinish: () => (confirmando.value = false) })
</script>

<template>
    <Head title="Mi turno" />
    <header class="mb-5 flex items-center gap-3">
        <Avatar class="size-14"><AvatarImage :src="yo.foto" alt="" /><AvatarFallback>{{ yo.corto[0] }}</AvatarFallback></Avatar>
        <div>
            <h1 class="text-2xl font-semibold">{{ yo.nombre }}</h1>
            <p class="text-muted-foreground">{{ yo.planta }} · Turno desde las {{ yo.desde }}<span v-if="yo.responsable"> · Responsable de turno</span></p>
        </div>
    </header>

    <div class="grid gap-4 md:grid-cols-2">
        <Card>
            <CardHeader>
                <CardDescription>Tu turno</CardDescription>
                <CardTitle class="text-3xl">{{ registrados }} de {{ total }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="h-2 overflow-hidden rounded-full bg-muted" role="progressbar" :aria-valuenow="pct" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full bg-success" :style="{ width: pct + '%' }" /></div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <Badge v-if="pendientes" variant="secondary">{{ pendientes }} por registrar</Badge>
                    <Badge v-if="borradores" variant="outline" class="border-warning/50 text-warning">{{ borradores }} {{ borradores === 1 ? 'borrador' : 'borradores' }} sin confirmar</Badge>
                    <Badge v-if="fuera" variant="outline">{{ fuera }} fuera</Badge>
                </div>
            </CardContent>
        </Card>

        <!-- Antes de irte: «sin novedad» en bloque, con una confirmación -->
        <Card :class="sinRegistro.length && 'border-primary/40'">
            <CardHeader>
                <CardDescription>Antes de irte</CardDescription>
                <CardTitle class="text-base">{{ sinRegistro.length ? `${sinRegistro.length} sin registro en este turno` : 'Todos tienen registro' }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <p v-if="sinRegistro.length && !finalDeTurno" class="text-sm text-muted-foreground">Al final de tu turno podrás marcar de una vez a quien no haya tenido novedad.</p>
                <template v-if="sinRegistro.length && finalDeTurno">
                    <p class="text-sm text-muted-foreground">{{ sinRegistro.map((r) => r.corto).join(', ') }}. Si no ha pasado nada, márcalos sin novedad de una vez: queda como prueba de que les has atendido.</p>
                    <div v-if="confirmando" class="space-y-2 rounded-lg bg-secondary p-3">
                        <p class="text-sm font-medium">¿Les has atendido y no ha pasado nada que contar?</p>
                        <div class="flex gap-2">
                            <Button class="h-11 flex-1 rounded-full" @click="marcar"><CheckCheck /> Sí, sin novedad</Button>
                            <Button variant="outline" class="h-11 rounded-full" @click="confirmando = false">No</Button>
                        </div>
                    </div>
                    <Button v-else size="lg" class="h-12 w-full rounded-full text-base" @click="confirmando = true"><CheckCheck /> Marcar {{ sinRegistro.length === 1 ? 'sin novedad' : `los ${sinRegistro.length} sin novedad` }}</Button>
                </template>
                <p v-if="borradores" class="text-sm text-warning">Tienes {{ borradores }} {{ borradores === 1 ? 'borrador' : 'borradores' }} sin confirmar: confírmalos en Mi planta.</p>
                <Button as-child variant="outline" size="lg" class="h-12 w-full rounded-full text-base"><Link href="/auxiliar/antes-de-irte"><StickyNote /> Dejar nota para el relevo</Link></Button>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle class="flex items-center gap-2 text-base"><Users class="size-4" /> Hoy contigo</CardTitle></CardHeader>
            <CardContent class="space-y-3">
                <div v-for="c in companeras" :key="c.nombre" class="flex items-center gap-3">
                    <Avatar class="size-9"><AvatarImage :src="c.foto" alt="" /><AvatarFallback>{{ c.nombre[0] }}</AvatarFallback></Avatar>
                    <span class="flex-1"><span class="block font-medium">{{ c.nombre }}</span><span class="text-sm text-muted-foreground">{{ c.planta }}</span></span>
                    <Badge :class="c.enTurno ? 'bg-success text-success-foreground' : ''" :variant="c.enTurno ? 'default' : 'outline'">{{ c.enTurno ? 'En turno' : 'Le toca hoy' }}</Badge>
                </div>
                <p v-if="!companeras.length" class="text-sm text-muted-foreground">Hoy estás sola en tu planta.</p>
            </CardContent>
        </Card>

        <Card class="md:col-span-2">
            <CardHeader><CardTitle class="text-base">Enfermería y medicina</CardTitle></CardHeader>
            <CardContent class="grid gap-3 sm:grid-cols-2">
                <div v-for="p in equipo" :key="p.nombre" class="flex items-center gap-3">
                    <Avatar class="size-9"><AvatarImage :src="p.foto" alt="" /><AvatarFallback>{{ p.nombre[0] }}</AvatarFallback></Avatar>
                    <span class="min-w-0 flex-1">
                        <span class="block font-medium">{{ p.nombre }} · {{ p.puesto }}</span>
                        <span class="block text-sm" :class="p.estado === 'aqui' ? 'text-success' : 'text-muted-foreground'">{{ p.texto }}</span>
                        <a v-if="p.contacto" :href="`tel:${String(p.contacto).replace(/\s+/g, '')}`" class="text-sm text-primary underline underline-offset-2">{{ p.contacto }}</a>
                    </span>
                </div>
            </CardContent>
        </Card>
    </div>

    <div class="mt-6 space-y-1">
        <Button variant="ghost" size="lg" class="h-12 rounded-full text-base" @click="router.post('/auxiliar/salir')"><LogOut /> Salir</Button>
        <p class="px-4 text-sm text-muted-foreground">Para que entre otra compañera en esta tablet. Tu turno sigue y termina solo al acabar tu horario.</p>
        <Button variant="link" class="px-4 text-base" @click="verGuia">¿Dudas? Ver la guía otra vez</Button>
    </div>
</template>
