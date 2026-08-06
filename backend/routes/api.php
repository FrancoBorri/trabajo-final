<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/login', [
    AuthController::class,
    'login'
]);


Route::post('/logout', [
    AuthController::class,
    'logout'
])->middleware('auth:sanctum');

Route::post('/register', [
    AuthController::class,
    'register'
]);

Route::apiResource('services', ServiceController::class)
    ->middleware('auth:sanctum');

Route::apiResource('users', UserController::class);
Route::apiResource('professionals', ProfessionalController::class);
Route::apiResource('availabilities', AvailabilityController::class);
Route::apiResource('appointments', AppointmentController::class);
