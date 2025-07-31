<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MercadoLibreOAuthService
{
    private const OAUTH_TOKEN_URL = 'https://api.mercadolibre.com/oauth/token';
    private const BASE_URL = 'https://api.mercadolibre.com/';
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
    public function getToken(
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
            return json_decode($response, true);

        } catch (Exception $e) {
            $this->errorLog('Exception during OAuth token exchange', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Refresh access token using refresh token
     *
     * @param string $refresh_token
     * @return array
     * @throws Exception
     */
    public function refreshToken(string $refresh_token, string $client_id, string $client_secret): array
    {
        $grant_type = 'refresh_token';
        $client_id = config('services.mercadolibre.client_id');
        $client_secret = config('services.mercadolibre.client_secret');
        $refresh_token = $refresh_token;
        
        try{
            // Initialize cURL session
            $ch = curl_init();
            
            // Set the URL
            $url = self::OAUTH_TOKEN_URL;
            curl_setopt($ch, CURLOPT_URL, $url);
            
            // Prepare data for refresh token request
            $data = [
                'grant_type' => $grant_type,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token
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
            
            // Check for API errors
            if ($httpCode >= 400) {
                throw new Exception('API error: ' . $response);
            }
            
            // Decode the response
            return json_decode($response, true);
        } catch (Exception $e) {
            $this->errorLog('Exception during OAuth token refresh', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Get user information from MercadoLibre API
     *
     * @param string $access_token
     * @return array
     * @throws Exception
     */
    public function getUserInfo(string $access_token): array
    {
        try {
            $response = $this->makeApiRequest(
                'users/me',
                $access_token,
                'GET'
            );
            return $response;

        } catch (Exception $e) {
            $this->errorLog('Exception during user info retrieval', $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Make API request to MercadoLibre using cURL with access token
     *
     * @param string $url
     * @param string $access_token
     * @param string $method
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function makeApiRequest(
        string $endpoint,
        string $access_token,
        string $method = 'GET',
        array $data = []
    ): array {
        try {
            // Initialize cURL session
            $ch = curl_init();
            
            // Set common cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $access_token,
                'Accept: application/json',
                'Content-Type: application/json'
            ]);
            
            // Set method-specific options
            if (strtoupper($method) === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                // For GET requests, append data as query parameters
                if (!empty($data)) {
                    $endpoint .= '?' . http_build_query($data);
                }
            }
            
            curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $endpoint);
            
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
            
            // Check if JSON decode failed
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response: ' . json_last_error_msg());
            }
            
            // Check if request was successful
            if ($httpCode >= 200 && $httpCode < 300) {
                return $responseData;
            }
            
            // Log the error response
            $this->errorLog('API request failed', "" , [
                'url' => self::BASE_URL . $endpoint,
                'method' => $method,
                'status_code' => $httpCode,
                'response' => $responseData,
                'access_token' => substr($access_token, 0, 10) . '...'
            ]);
            
            throw new Exception('API request failed: HTTP ' . $httpCode . ' - ' . json_encode($responseData));
            
        } catch (Exception $e) {
            $this->errorLog('Exception during API request', $e->getMessage(), [
                'url' => self::BASE_URL . $endpoint,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    private function errorLog(string $title, string $message, array $data = []): void
    {
        Log::error(
            $title, 
            array_merge(
                [
                    'message' => $message,
                ],
                $data
            )
        );
    }


    
} 