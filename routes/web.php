<?php

use App\Http\Controllers\AiSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EditionController;
use App\Http\Controllers\EvaluationCriterionController;
use App\Http\Controllers\ModalityController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckAdmin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// Rotas públicas
// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

// Rotas publicas
Route::get('/editions/regulations/{file}', [EditionController::class, 'showRegulation'])
    ->name('editions.regulations.show');

Route::get('/forms/edition', [EditionController::class, 'formEdition'])
    ->name('form.edition');

Route::get('/json/estados-cidades', function () {
    $path = public_path('json/estados_cidades.json');
    $content = File::get($path);

    return response($content, 200)
        ->header('Content-Type', 'application/json')
        ->header('Access-Control-Allow-Origin', '*');
});

// Rotas de autenticação
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendReset'])->name('password.email');

    // password.reset
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password/{token}', [AuthController::class, 'reset'])->name('password.resetpost');

});

// Rotas autenticadas
Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/logout', [AuthController::class, 'logout'])->name('sair');

    Route::prefix('admin')->group(function () {
        $middleware = ['auth', CheckAdmin::class.':admin'];

        Route::resource('users', UserController::class)->middleware($middleware)->names([
            'index' => 'admin.users.index',
            'create' => 'admin.users.create',
            'store' => 'admin.users.store',
            // 'show' => 'admin.users.show',
            'edit' => 'admin.users.edit',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);

        Route::resource('editions', EditionController::class)->middleware($middleware)->names([
            'index' => 'admin.editions.index',
            'create' => 'admin.editions.create',
            'store' => 'admin.editions.store',
            // 'show' => 'admin.editions.show',
            'edit' => 'admin.editions.edit',
            'update' => 'admin.editions.update',
            'destroy' => 'admin.editions.destroy',
        ]);

        Route::resource('modalities', ModalityController::class)->middleware($middleware)->names([
            'index' => 'admin.modalities.index',
            'create' => 'admin.modalities.create',
            'store' => 'admin.modalities.store',
            'edit' => 'admin.modalities.edit',
            'update' => 'admin.modalities.update',
            'destroy' => 'admin.modalities.destroy',
        ]);

        Route::resource('categories', CategoryController::class)->middleware($middleware)->names([
            'index' => 'admin.categories.index',
            'create' => 'admin.categories.create',
            'store' => 'admin.categories.store',
            // 'show' => 'admin.editions.show',
            'edit' => 'admin.categories.edit',
            'update' => 'admin.categories.update',
            'destroy' => 'admin.categories.destroy',
        ]);

        Route::resource('criteria', EvaluationCriterionController::class)->middleware($middleware)->names([
            'index' => 'admin.criteria.index',
            'create' => 'admin.criteria.create',
            'store' => 'admin.criteria.store',
            // 'show' => 'admin.editions.show',
            'edit' => 'admin.criteria.edit',
            'update' => 'admin.criteria.update',
            'destroy' => 'admin.criteria.destroy',
        ]);

        $middlewareListaInscricoes = ['auth', CheckAdmin::class.':admin,comission'];
        Route::resource('registration', RegistrationController::class)->middleware($middlewareListaInscricoes)->names([
            'index' => 'admin.registration.index',
        ]);
        Route::get('/admin/registration/show/{id}/{type}', [RegistrationController::class, 'show'])->middleware($middlewareListaInscricoes)->name('admin.registration.show');

        Route::post('/admin/registration/rejeitar/{id}/{type}', [RegistrationController::class, 'rejeitar'])->middleware($middleware)->name('admin.registration.rejeitar');
        Route::post('/admin/registration/habilitar/{id}/{type}', [RegistrationController::class, 'habilitar'])->middleware($middleware)->name('admin.registration.habilitar');
        Route::post('/admin/registration/indicar/{id}', [RegistrationController::class, 'indicar'])->middleware($middlewareListaInscricoes)->name('admin.registration.indicar');
        Route::post('/admin/registration/send-opinion/{registration}', [RegistrationController::class, 'sendOpinion'])->middleware($middlewareListaInscricoes)->name('admin.registration.send.opinion');

        Route::get('/admin/registration/file/{file}', [RegistrationController::class, 'file'])->middleware($middlewareListaInscricoes)->name('admin.registration.file');
        Route::get('/admin/users/{user}/login-as', [UserController::class, 'loginAs'])->middleware($middleware)->name('admin.users.login-as');
        Route::get('/admin/users/return-to-admin', [UserController::class, 'returnToAdmin'])->name('admin.users.return-to-admin');

        Route::get('/ai-settings', [AiSettingsController::class, 'index'])->middleware($middleware)->name('admin.ai-settings.index');
        Route::put('/ai-settings', [AiSettingsController::class, 'update'])->middleware($middleware)->name('admin.ai-settings.update');
        Route::post('/ai-settings/models', [AiSettingsController::class, 'refreshModels'])->middleware([...$middleware, 'throttle:5,1'])->name('admin.ai-settings.models');
        Route::post('/ai-settings/test', [AiSettingsController::class, 'testConnection'])->middleware([...$middleware, 'throttle:5,1'])->name('admin.ai-settings.test');
        Route::post('/ai-settings/process/{type}', [AiSettingsController::class, 'process'])->whereIn('type', ['technical', 'selection'])->middleware($middleware)->name('admin.ai-settings.process');

    });

});
