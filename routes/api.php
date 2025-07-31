<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;

Route::post('/oauth/token', [OAuthController::class, 'exchangeToken']);
Route::post('/oauth/token/direct', [OAuthController::class, 'exchangeTokenDirect']); 