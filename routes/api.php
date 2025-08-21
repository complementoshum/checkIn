<?php

use App\Http\Controllers\Apis\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/generate-qr', [UserController::class, 'generateQrCode']);
Route::get('/user/{id}', [UserController::class, 'getUser']);

Route::get('/check-in/{id}', [UserController::class, 'checkIn']);

Route::post('/send-messages', [UserController::class, 'sendMessage']);


Route::post('/generate-invitation', [UserController::class, 'generateInvitation']);