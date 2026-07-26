<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Finance\FraisScolariteController;
use App\Http\Controllers\Api\Parametrage\AnneeAcademiqueController;
use App\Http\Controllers\Api\Parametrage\ClasseController;
use App\Http\Controllers\Api\Parametrage\EtablissementController;
use App\Http\Controllers\Api\Parametrage\FiliereController;
use App\Http\Controllers\Api\Parametrage\MatiereController;
use App\Http\Controllers\Api\Parametrage\NiveauController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::middleware('role:super_admin|admin_etablissement')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::apiResource('etablissements', EtablissementController::class)->except('show');
        Route::apiResource('annees-academiques', AnneeAcademiqueController::class)->except('show')
            ->parameters(['annees-academiques' => 'anneeAcademique']);
        Route::apiResource('filieres', FiliereController::class)->except('show');
        Route::apiResource('classes', ClasseController::class)->except('show')
            ->parameters(['classes' => 'classe']);
        Route::apiResource('matieres', MatiereController::class)->except('show');

        Route::get('/filieres/{filiere}/niveaux', [NiveauController::class, 'index']);
        Route::post('/niveaux', [NiveauController::class, 'store']);
        Route::put('/niveaux/{niveau}', [NiveauController::class, 'update']);
        Route::delete('/niveaux/{niveau}', [NiveauController::class, 'destroy']);

        Route::apiResource('frais-scolarite', FraisScolariteController::class)->except('show')
            ->parameters(['frais-scolarite' => 'fraisScolarite']);
    });
});
