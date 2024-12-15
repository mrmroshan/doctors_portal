<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(OdooApi::class, function ($app) {
            return new OdooApi();
        });
    
        $this->app->singleton(PatientService::class, function ($app) {
            return new PatientService($app->make(OdooApi::class));
        });
    
        $this->app->singleton(PrescriptionService::class, function ($app) {
            return new PrescriptionService(
                $app->make(OdooApi::class),
                $app->make(PatientService::class)
            );
        });
        
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.simple-custom');
    }
}
