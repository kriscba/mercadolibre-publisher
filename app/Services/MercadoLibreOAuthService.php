<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MercadoLibreOAuthService
{
    private const OAUTH_TOKEN_URL = 'https://api.mercadolibre.com/oauth/token';
    private const GRANT_TYPE = 'authorization_code';

    /**
     * Exchange authorization code for access token
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $code
     * @param string $redirectUri
     * @return array
     * @throws Exception
     */
    public function exchangeToken(
        string $grant_type,
        string $client_id,
        string $client_secret,
        string $redirect_uri,
        string $code,
    ): array {
        try {
            // Initialize cURL session
            $ch = curl_init(self::OAUTH_TOKEN_URL);
            
            // Prepare the request data
            $data = [
                'grant_type' => $grant_type,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => $redirect_uri,
            ];
            
            // Set cURL options
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            
            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            // Close cURL
            curl_close($ch);
            
            // Check for cURL errors
            if ($error) {
                throw new Exception('cURL error: ' . $error);
            }
            
            // Decode the response
            $responseData = json_decode($response, true);
            return $responseData;
            // // dd($responseData);
            
            // // Check if JSON decode failed
            // if (json_last_error() !== JSON_ERROR_NONE) {
            //     throw new Exception('Invalid JSON response: ' . json_last_error_msg());
            // }
            
            // // Check if request was successful
            // if ($httpCode >= 200 && $httpCode < 300) {
            //     return $responseData;
            // }


            // throw new Exception('Failed to exchange token: ' . $response->body());

        } catch (Exception $e) {
            Log::error('Exception during OAuth token exchange', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
} 