<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EditionController;

Route::get('/has-registrations', [EditionController::class, 'hasRegistrations']);
Route::post('/registration', [EditionController::class, 'registration']);
Route::get('/candidato/{cpf}', [EditionController::class, 'findByCpf']);
Route::get('/requestToken/{protocol}/{actionType}', [EditionController::class, 'requestTokenAction']);
Route::get('/verifyToken/{token}', [EditionController::class, 'consumeToken'])->name('consume.token');
Route::post('/verifyToken/{token}', [EditionController::class, 'consumeTokenPost']);
