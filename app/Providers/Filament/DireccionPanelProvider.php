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
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use App\Support\TemaVelia;
use Filament\FontProviders\GoogleFontProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/** Web de la dirección. Menú: Inicio, Equipo, Residentes, Calidad y auditoría, Familias, Mi residencia, Ayuda. */
class DireccionPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Tema compilado (resources/css/filament/direccion): píldoras, tarjetas y clases propias de las vistas.
        // Solo se activa si se ha compilado con npm run build; sin él el panel funciona con el aspecto base de Filament.
        if (file_exists(public_path('build/manifest.json'))) {
            $panel->viteTheme('resources/css/filament/direccion/theme.css');
        }

        // Cada panel con su propia sesión: entrar con PIN en la tablet no cierra la sesión de dirección (ni al revés).
        config()->set('auth.guards.direccion', ['driver' => 'session', 'provider' => 'users']);

        return $panel
            ->default()
            ->id('direccion')
            ->authGuard('direccion')
            ->path('direccion')
            ->login()
            ->passwordReset()
            ->brandName('Velia')
            ->colors(TemaVelia::colores())
            ->darkMode(false)
            ->font('Albert Sans', provider: GoogleFontProvider::class)
            ->brandLogo(fn () => view('filament.marca.logo'))
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
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render("@include('filament.marca.ajustes-finos')" . "@include('filament.marca.hover-accion')"))
            ->authMiddleware([Authenticate::class]);
    }

    public function boot(): void
    {
        // Iconos de borrar y quitar en trazo fino (outline), no macizos: menos peso visual en acciones destructivas.
        FilamentIcon::register([
            'forms::components.repeater.actions.delete' => 'heroicon-o-trash',
            'forms::components.builder.actions.delete' => 'heroicon-o-trash',
            'forms::components.key-value.actions.delete' => 'heroicon-o-trash',
            'actions::delete-action' => 'heroicon-o-trash',
            'actions::delete-action.modal' => 'heroicon-o-trash',
            'tables::actions.delete' => 'heroicon-o-trash',
        ]);
    }
}
