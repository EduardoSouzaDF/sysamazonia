<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EditionController;

Route::get('/has-registrations', [EditionController::class, 'hasRegistrations']);
Route::get('/categories', [EditionController::class, 'getCategories']);
Route::post('/registration', [EditionController::class, 'registration']);
Route::get('/candidato/{cpf}', [EditionController::class, 'findByCpf']);
