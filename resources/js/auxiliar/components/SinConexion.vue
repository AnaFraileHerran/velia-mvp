<script setup lang="ts">
/** Regla 3: nada bloquea un registro. Sin red, se avisa y lo escrito se guarda en el dispositivo hasta que vuelve. */
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { WifiOff } from '@lucide/vue'

const sinRed = ref(!navigator.onLine)
const ver = () => { sinRed.value = !navigator.onLine }
onMounted(() => { window.addEventListener('online', ver); window.addEventListener('offline', ver) })
onBeforeUnmount(() => { window.removeEventListener('online', ver); window.removeEventListener('offline', ver) })
</script>

<template>
    <div v-if="sinRed" role="status" aria-live="polite" class="fixed inset-x-0 top-0 z-40 flex items-center justify-center gap-2 bg-foreground px-4 py-2.5 text-sm text-background">
        <WifiOff class="size-4" aria-hidden="true" />
        Sin conexión. Lo que escribas se guarda en este dispositivo y se enviará al volver la conexión.
    </div>
</template>
