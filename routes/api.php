<?php

use App\Http\Controllers\Apis\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/generate-qr', [UserController::class, 'generateQrCode']);
Route::get('/user/{id}', [UserController::class, 'getUser']);

Route::post('/check-in', [UserController::class, 'checkIn']);

Route::post('/send-messages', [UserController::class, 'sendMessage']);