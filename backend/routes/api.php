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

Route::apiResource(
    'availability',
    AvailabilityController::class
)->middleware('auth:sanctum');

Route::get(
    '/professionals/{professional}/available-slots',
    [AvailabilityController::class, 'availableSlots']
);

// Get appointments for a specific professional
Route::get('/professional/appointments', [
    AppointmentController::class,
    'professionalAppointments'
])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/professional/profile', [
        ProfessionalController::class,
        'profile'
    ]);

    Route::put('/professional/profile', [
        ProfessionalController::class,
        'updateProfile'
    ]);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('appointments', AppointmentController::class);

    Route::patch('/appointments/{appointment}/cancel', [
        AppointmentController::class,
        'cancel'
    ]);

    Route::patch('/appointments/{appointment}/complete', [
        AppointmentController::class,
        'complete'
    ]);
});

// Admin route to get all appointments
Route::get('/admin/appointments', [
    AppointmentController::class,
    'adminAppointments'
])->middleware('auth:sanctum');


Route::apiResource('professionals', ProfessionalController::class);
Route::apiResource('services', ServiceController::class)->middleware('auth:sanctum');
Route::apiResource('users', UserController::class);
