<?php

namespace App\Providers\Filament;

use App\Filament\Direccion\Pages\Ayuda;
use App\Filament\Direccion\Pages\Calidad;
use App\Filament\Direccion\Pages\Inicio;
use App\Filament\Direccion\Pages\MiResidencia;
use App\Filament\Direccion\Pages\Ocupacion;
use App\Support\TemaVelia;
use App\Support\VersionH;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Versión H de la web de dirección (25/11/2026) en /nueva-version (antes /direccion-h). Mismas pantallas, datos y sesión que /direccion
 * (mismo guard: si entras en una, entras en la otra), con los patrones de Holded que se quieren probar:
 * «+ Crear» en la barra superior, Inicio con pestañas, listas con pestañas de estado y ayuda por sección.
 */
class DireccionHPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        if (file_exists(public_path('build/manifest.json'))) {
            $panel->viteTheme('resources/css/filament/direccion/theme.css');
        }

        config()->set('auth.guards.direccion', ['driver' => 'session', 'provider' => 'users']);

        return $panel
            ->id(VersionH::PANEL)
            ->authGuard('direccion')
            ->path('nueva-version')
            ->login()
            ->brandName('Velia')
            ->colors(TemaVelia::colores())
            ->darkMode(false)
            ->font('Albert Sans', provider: GoogleFontProvider::class)
            ->brandLogo(fn () => view('filament.marca.logo'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.svg'))
            // Listas a todo el ancho, como Holded.
            ->maxContentWidth('full')
            // Menú lateral más estrecho y siempre abierto (comentarios de Pablo y Antonio): los nombres son cortos
            // y en estas herramientas la altura es lo que más falta; la navegación arriba restaría alto.
            ->sidebarWidth('14rem')
            // Un solo buscador (el de Velia); se quita el de Filament para no tener dos campos.
            ->globalSearch(false)
            ->discoverResources(in: app_path('Filament/Direccion/Resources'), for: 'App\\Filament\\Direccion\\Resources')
            ->pages([Inicio::class, Calidad::class, MiResidencia::class, Ocupacion::class, Ayuda::class])
            ->discoverWidgets(in: app_path('Filament/Direccion/Widgets'), for: 'App\\Filament\\Direccion\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render("@include('filament.marca.ajustes-finos')" . "@include('filament.marca.hover-accion')" . "@include('filament.marca.dictado')"))
            // Barra superior al estilo Holded: aviso de versión, «+ Crear» e «Ir a… ⌘K».
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => VersionH::activa() ? Blade::render("@include('filament.direccion-h.version')@include('filament.direccion-h.crear')@include('filament.direccion-h.buscar')") : '')
            // Ayuda de la pantalla: un «?» pegado al título (25/11/2026). No junto a las acciones (esa zona es para lo frecuente,
            // comentario de Pablo) ni flotante (el flotante es para la acción principal y en la auxiliar ya es el micro).
            ->renderHook(PanelsRenderHook::PAGE_HEADER_HEADING_AFTER, fn (array $scopes) => VersionH::activa() && ($ayuda = VersionH::ayudaSeccion($scopes)) ? view('filament.direccion-h.ayuda-seccion', ['ayuda' => $ayuda])->render() : '')
            ->renderHook(PanelsRenderHook::BODY_END, fn () => VersionH::activa() && filament()->auth()->check() ? Blade::render("@include('filament.direccion.partials.ir-a')") : '')
            ->authenticatedRoutes(function () {
                // Respuesta rápida del buscador: solo recibe la pregunta escrita, nunca datos de residentes (regla 10).
                \Illuminate\Support\Facades\Route::get('respuesta-rapida', function (\Illuminate\Http\Request $request) {
                    $q = trim((string) $request->query('q', ''));
                    if (mb_strlen($q) < 3) {
                        return response()->json(null);
                    }
                    $r = \App\Support\Ia\Asistente::responder($q);

                    return response()->json(['texto' => $r['texto'], 'enlaces' => array_slice($r['enlaces'] ?? [], 0, 3), 'ejemplo' => \App\Support\Ia\Asistente::esSimulado()]);
                })->name('respuesta-rapida');
            })
            ->authMiddleware([Authenticate::class]);
    }
}
