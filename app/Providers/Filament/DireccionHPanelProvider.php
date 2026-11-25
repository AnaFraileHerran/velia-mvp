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
 * Versión H de la web de dirección (25/11/2026) en /direccion-h. Mismas pantallas, datos y sesión que /direccion
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
            ->path('direccion-h')
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
            ->sidebarWidth('15rem')
            ->sidebarCollapsibleOnDesktop()
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
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => VersionH::activa() ? Blade::render("@include('filament.direccion-h.version')@include('filament.direccion-h.crear')@include('filament.direccion.partials.ir-a-boton')") : '')
            // Libro de ayuda de la sección, junto a las acciones de cada pantalla.
            ->renderHook(PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE, fn (array $scopes) => VersionH::activa() && ($ayuda = VersionH::ayudaSeccion($scopes)) ? view('filament.direccion-h.ayuda-seccion', ['ayuda' => $ayuda])->render() : '')
            ->renderHook(PanelsRenderHook::BODY_END, fn () => VersionH::activa() && filament()->auth()->check() ? Blade::render("@include('filament.direccion.partials.ir-a')") : '')
            ->authMiddleware([Authenticate::class]);
    }
}
