<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MercadoLibre\OAuthController;
use App\Http\Controllers\MercadoLibre\MLUserController;
use App\Http\Controllers\ItemController;

Route::get('/', function () {
    return view('welcome');
});

// OAuth Routes
Route::get('/user-info', [MLUserController::class, 'getAdmin'])->name('oauth.user-info');
Route::get('/user-create', [MLUserController::class, 'createUserTest'])->name('oauth.create-user');
Route::get('/items-category', [ItemController::class, 'getItemCategory'])->name('items.category');
Route::get('/items-validate', [ItemController::class, 'validateItem'])->name('items.validate');

Route::prefix('oauth')->group(function () {
    // Route for OAuth token exchange using the service
    Route::post('/exchange-token', [OAuthController::class, 'getToken'])->name('oauth.exchange-token');
    
    // Route for OAuth test interface
    Route::get('/test', function () {
        return view('oauth-test');
    })->name('oauth.test');
    
    // Route for token management interface
    Route::get('/manage', function () {
        return view('token-management');
    })->name('oauth.manage');
    
    // Token management routes
    Route::get('/tokens', [OAuthController::class, 'getTokens'])->name('oauth.tokens');
    Route::get('/tokens/{id}', [OAuthController::class, 'getToken'])->name('oauth.token.show');
    Route::delete('/tokens/{id}', [OAuthController::class, 'revokeToken'])->name('oauth.token.revoke');
    Route::post('/tokens/cleanup', [OAuthController::class, 'cleanupExpiredTokens'])->name('oauth.tokens.cleanup');
    
    // Optional: GET route for OAuth documentation/status
    Route::get('/status', function () {
        return response()->json([
            'status' => 'OAuth endpoints are available',
            'endpoints' => [
                'POST /oauth/exchange-token' => 'Exchange authorization code for access token (using service)',
                'POST /oauth/exchange-token-direct' => 'Exchange authorization code for access token (using direct cURL)',
                'GET /oauth/test' => 'OAuth test interface',
                'GET /oauth/tokens' => 'Get stored tokens (with optional filters)',
                'GET /oauth/tokens/{id}' => 'Get specific token',
                'DELETE /oauth/tokens/{id}' => 'Revoke specific token',
                'POST /oauth/tokens/cleanup' => 'Clean up expired tokens',
            ],
            'required_parameters' => [
                'grant_type' => 'string (e.g., authorization_code)',
                'client_id' => 'string (your MercadoLibre app client ID)',
                'client_secret' => 'string (your MercadoLibre app client secret)',
                'code' => 'string (authorization code from MercadoLibre)',
                'redirect_uri' => 'string (your app redirect URI)',
                'code_verifier' => 'string (PKCE code verifier)',
            ],
            'token_filters' => [
                'client_id' => 'Filter tokens by client ID',
                'user_id' => 'Filter tokens by user ID',
                'status' => 'Filter tokens by status (active, expired, revoked)',
            ]
        ]);
    })->name('oauth.status');
});
