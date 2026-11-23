<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Solo en local (demo): si hay cambios de base de datos pendientes, se aplican solos al abrir Velia.
        // Así no hace falta ejecutar «php artisan migrate» a mano tras cada cambio.
        if ($this->app->environment('local') && ! $this->app->runningInConsole()) {
            try {
                $migrator = $this->app->make('migrator');
                if ($migrator->repositoryExists()) {
                    $hechas = $migrator->getRepository()->getRan();
                    $archivos = array_keys($migrator->getMigrationFiles([database_path('migrations')]));
                    if (array_diff($archivos, $hechas)) {
                        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    }
                }
                \App\Support\DatosDemo::asegurar();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
