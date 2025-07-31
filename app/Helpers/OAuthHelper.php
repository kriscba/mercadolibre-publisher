<?php


namespace App\Helpers;

use App\Models\OAuthToken;
use App\Services\MercadoLibreOAuthService;

class OAuthHelper
{
    public static function getActiveToken(string $client_id): ?string
    {
        $client_secret = config('services.mercadolibre.client_secret');

        $token = OAuthToken::where('client_id', $client_id)->where('status', 'active')->first();
        if($token->isExpired()){
            $token = self::refreshToken($token->refresh_token, $client_id, $client_secret);
        }

        return $token->access_token;
    }

    public static function refreshToken(string $refresh_token){
        
        $client_id = config('services.mercadolibre.client_id');
        $client_secret = config('services.mercadolibre.client_secret');
        $oauthService = new MercadoLibreOAuthService();
        $refreshToken = $oauthService->refreshToken($refresh_token, $client_id, $client_secret);

        $token = OAuthToken::createFromOAuthResponse($refreshToken);
        if($token instanceof OAuthToken){
            return $token;
        }
        
        return null;
    }
}