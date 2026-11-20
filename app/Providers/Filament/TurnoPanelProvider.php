<?php

namespace App\Providers\Filament;

use App\Filament\Turno\Pages\Auth\PinLogin;
use App\Filament\Turno\Acceso\PrimerAcceso;
use App\Filament\Turno\Acceso\RestablecerPin;
use App\Http\Middleware\TurnoAbierto;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use App\Support\TemaVelia;
use Filament\FontProviders\GoogleFontProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * App de las auxiliares (tablet compartida o móvil). Sin menú lateral: barra inferior de 3 pestañas
 * (Relevo · Residentes · Mi turno), como en «Wireframes v2 · propuesta UX». Debe funcionar a 390 px.
 */
class TurnoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        // Tema compilado (resources/css/filament/turno): píldoras, tarjetas y clases propias de las vistas.
        // Solo se activa si se ha compilado con npm run build; sin él el panel funciona con el aspecto base de Filament.
        if (file_exists(public_path('build/manifest.json'))) {
            $panel->viteTheme('resources/css/filament/turno/theme.css');
        }

        // Cada panel con su propia sesión: entrar con PIN en la tablet no cierra la sesión de dirección (ni al revés).
        config()->set('auth.guards.turno', ['driver' => 'session', 'provider' => 'users']);

        return $panel
            ->id('turno')
            ->authGuard('turno')
            ->path('turno')
            ->login(PinLogin::class)
            ->brandName('Velia')
            ->colors(TemaVelia::colores())
            ->darkMode(false)
            // Albert Sans en todo; Atkinson Hyperlegible Next solo en PIN y números (ver partials/estilos).
            ->font('Albert Sans', provider: GoogleFontProvider::class)
            ->brandLogo(fn () => view('filament.marca.logo'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.svg'))
            ->navigation(false)
            ->topbar(false)
            ->maxContentWidth('3xl')
            ->discoverPages(in: app_path('Filament/Turno/Pages'), for: 'App\\Filament\\Turno\\Pages')
            // Pantallas sin sesión: primer acceso y restablecer PIN (lo hace la responsable de turno).
            ->routes(function () {
                Route::get('/primer-acceso', PrimerAcceso::class)->name('primer-acceso');
                Route::get('/restablecer-pin', RestablecerPin::class)->name('restablecer-pin');
            })
            ->renderHook(PanelsRenderHook::BODY_END, fn () => \Filament\Facades\Filament::auth()->check() ? Blade::render("@include('filament.turno.partials.barra-inferior')") : '')
            // Botones y textos más grandes: se usa de pie, con prisa y a veces con guantes.
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render("@include('filament.turno.partials.estilos')"))
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
            ->authMiddleware([Authenticate::class, TurnoAbierto::class]);
    }
}
