<?php

namespace App\Providers;

use App\Models\Nominee;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Morph map global (spec 0003): aliases estáveis para as Inscrições (RDD-02),
        // evitando FQCN nas colunas `*_type` das tabelas morph.
        Relation::enforceMorphMap([
            'registration' => Registration::class,
            'nominee' => Nominee::class,
        ]);
    }
}
