// Peticiones JSON con la protección CSRF de Laravel (cookie XSRF-TOKEN), para lo que no cambia de pantalla.
export async function postJson<T = any>(url: string, body: Record<string, unknown>): Promise<T> {
    const xsrf = decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '')
    const r = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(body),
    })
    const data = await r.json().catch(() => ({}))
    if (!r.ok) throw new Error(data.error || data.message || 'No se ha podido guardar. Prueba otra vez.')
    return data as T
}
