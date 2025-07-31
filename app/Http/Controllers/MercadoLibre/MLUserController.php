<?php

namespace App\Http\Controllers\MercadoLibre;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OAuthToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\MercadoLibreOAuthService;

class MLUserController extends Controller
{
    protected MercadoLibreOAuthService $oauthService;

    public function __construct(MercadoLibreOAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    
    /**
     * Get user information from MercadoLibre
     */
    public function getAdmin(Request $request): JsonResponse
    {
        $access_token = OAuthToken::where('status', 'active')->first()->access_token;
        if (!$access_token) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'No active token found'
            ], 404);
        }
        $userInfo = $this->oauthService->getUserInfo($access_token);
        return response()->json($userInfo);
    }
    
    public function createUserTest(): JsonResponse
    {
        try {
            // Get an active token
            $token = OAuthToken::where('status', 'active')->first();
            
            if (!$token) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'No active token found'
                ], 404);
            }
            
            // User data to create
            $password = 'TestPassword123';
            $nickname = 'mitest' . rand(1000, 9999);
            $email = 'mitest' . time() . '@testuser.com';

            $data = [
                'site_id' => 'MLB',
                'email' => $email,
                'password' => $password,
                'nickname' => $nickname
            ];
            
            // Make API request to create test user
            // $response = $this->oauthService->makeApiRequest(
            //     'users/test_user',
            //     $token->access_token,
            //     'POST',
            //     $data
            // );

            $response = '{"success":true,"message":"Test user created successfully","user":{"id":2594791806,"email":"test_user_1311059247@testuser.com","nickname":"TESTUSER1311059247","site_status":"active","password":"MTXzUe5Jjg"}}';
            $response = json_decode($response, true);


            $userData = $response['user'];

            $user = User::createFromOAuthResponse($userData, $token);

            return response()->json([
                'success' => true,
                'message' => 'Test user created successfully',
                'user' => $response
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create test user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'api_error',
                'message' => 'Failed to create test user: ' . $e->getMessage()
            ], 500);
        }
    }   
}
