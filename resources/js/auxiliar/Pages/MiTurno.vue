<script setup lang="ts">
/** Mi turno: cómo va (registrados, borradores, pendientes), quién trabaja hoy y cerrar el turno o cambiar de persona. */
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { LogOut, Users } from '@lucide/vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

const props = defineProps<{ registrados: number; total: number; borradores: number; pendientes: number; fuera: number; companeras: any[]; equipo: any[] }>()
const yo = usePage<any>().props.yo
const pct = props.total ? Math.round((props.registrados / props.total) * 100) : 0
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
                <Button as-child size="lg" class="h-12 w-full rounded-full text-base"><Link href="/auxiliar/cerrar">Cerrar el turno</Link></Button>
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

    <Button variant="ghost" size="lg" class="mt-6 h-12 rounded-full text-base" @click="router.post('/auxiliar/salir')"><LogOut /> Cambiar de persona</Button>
</template>
