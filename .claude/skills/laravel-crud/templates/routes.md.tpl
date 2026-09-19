<?php // Template de rotas — adicione em routes/web.php dentro do grupo 'admin'
// Referência: Route::resource('users', UserController::class) em routes/web.php

use App\Http\Controllers\{{Model}}Controller;

Route::resource('{{snake}}', {{Model}}Controller::class)
    ->middleware(['auth', CheckAdmin::class.':admin'])
    ->names([
        'index'   => 'admin.{{snake}}.index',
        'create'  => 'admin.{{snake}}.create',
        'store'   => 'admin.{{snake}}.store',
        // 'show'  => 'admin.{{snake}}.show',
        'edit'    => 'admin.{{snake}}.edit',
        'update'  => 'admin.{{snake}}.update',
        'destroy' => 'admin.{{snake}}.destroy',
    ]);
