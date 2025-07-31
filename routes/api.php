<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MercadoLibre\OAuthController;

Route::post('/oauth/token', [OAuthController::class, 'getToken']);
Route::post('/oauth/token/direct', [OAuthController::class, 'getTokenDirect']); 