<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\MuscleController;
use App\Http\Controllers\Api\TrainingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Публичные роуты (без авторизации)
Route::prefix('v1')->group(function () {
    // Авторизация
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Публичные данные (можно сделать доступными без авторизации)
    Route::get('/muscles', [MuscleController::class, 'index']);
    Route::get('/muscles/{id}', [MuscleController::class, 'show']);
    Route::get('/exercises', [ExerciseController::class, 'index']);
    Route::get('/exercises/{id}', [ExerciseController::class, 'show']);
});

// Защищенные роуты (требуют авторизации)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Пользователь
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Мышцы (полный CRUD для авторизованных)
    Route::get('/muscles', [MuscleController::class, 'index']);
    Route::get('/muscles/{id}', [MuscleController::class, 'show']);

    // Упражнения (полный CRUD для авторизованных)
    Route::get('/exercises', [ExerciseController::class, 'index']);
    Route::get('/exercises/{id}', [ExerciseController::class, 'show']);
    Route::post('/exercises', [ExerciseController::class, 'store']);
    Route::put('/exercises/{id}', [ExerciseController::class, 'update']);
    Route::delete('/exercises/{id}', [ExerciseController::class, 'destroy']);

    // Тренировки (только для авторизованных)
    Route::apiResource('trainings', TrainingController::class);
});
