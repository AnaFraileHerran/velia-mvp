<script setup lang="ts">
/** Fila de residente: foto, nombre y habitación, y lo último que se sabe. Se toca para ver «Cómo atender». */
import { ChevronRight } from '@lucide/vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'

defineProps<{ residente: any; texto?: string | null; detalle?: string | null; incidencia?: boolean; etiqueta?: string | null }>()
defineEmits<{ abrir: [] }>()
</script>

<template>
    <button type="button" class="flex min-h-16 w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-accent focus-visible:bg-accent" @click="$emit('abrir')">
        <Avatar class="size-11"><AvatarImage :src="residente.foto" alt="" /><AvatarFallback>{{ residente.nombre[0] }}</AvatarFallback></Avatar>
        <span class="min-w-0 flex-1">
            <span class="flex flex-wrap items-center gap-2">
                <span class="font-semibold">{{ residente.nombre }}</span>
                <Badge v-if="incidencia" variant="destructive">Incidencia</Badge>
                <Badge v-else-if="etiqueta" variant="secondary">{{ etiqueta }}</Badge>
            </span>
            <span class="block text-sm text-foreground/80"><span class="text-muted-foreground">Hab. {{ residente.habitacion }}</span><template v-if="texto"> · {{ texto }}</template></span>
            <span v-if="detalle" class="block text-xs text-muted-foreground">{{ detalle }}</span>
        </span>
        <ChevronRight class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
    </button>
</template>
