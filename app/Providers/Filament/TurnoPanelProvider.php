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
use Filament\Support\Colors\Color;
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
        return $panel
            ->id('turno')
            ->path('turno')
            ->login(PinLogin::class)
            ->brandName('Velia')
            ->colors(['primary' => Color::Amber])
            ->font('Inter')
            ->navigation(false)
            ->topbar(false)
            ->maxContentWidth('3xl')
            ->discoverPages(in: app_path('Filament/Turno/Pages'), for: 'App\\Filament\\Turno\\Pages')
            // Pantallas sin sesión: primer acceso y restablecer PIN (lo hace la responsable de turno).
            ->routes(function () {
                Route::get('/primer-acceso', PrimerAcceso::class)->name('primer-acceso');
                Route::get('/restablecer-pin', RestablecerPin::class)->name('restablecer-pin');
            })
            ->renderHook(PanelsRenderHook::BODY_END, fn () => auth()->check() ? Blade::render("@include('filament.turno.partials.barra-inferior')") : '')
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
