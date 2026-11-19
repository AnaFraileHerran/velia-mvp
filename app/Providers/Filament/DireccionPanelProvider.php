<?php

namespace App\Providers\Filament;

use App\Filament\Direccion\Pages\Inicio;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use App\Support\TemaVelia;
use Filament\FontProviders\GoogleFontProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/** Web de la dirección. Menú: Inicio, Equipo, Residentes, Calidad y auditoría, Familias, Mi residencia. */
class DireccionPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('direccion')
            ->path('direccion')
            ->login()
            ->passwordReset()
            ->brandName('Velia')
            ->colors(TemaVelia::colores())
            ->darkMode(false)
            ->font('Instrument Sans', provider: GoogleFontProvider::class)
            ->brandLogo(asset('images/velia-logo.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.svg'))
            ->maxContentWidth('7xl')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Direccion/Resources'), for: 'App\\Filament\\Direccion\\Resources')
            ->discoverPages(in: app_path('Filament/Direccion/Pages'), for: 'App\\Filament\\Direccion\\Pages')
            ->pages([Inicio::class])
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
            ->authMiddleware([Authenticate::class]);
    }
}
