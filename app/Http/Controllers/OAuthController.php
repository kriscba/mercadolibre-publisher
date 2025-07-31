<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\MercadoLibreOAuthService;
use App\Models\OAuthToken;
use Illuminate\Support\Facades\Log;

class OAuthController extends Controller
{
    protected MercadoLibreOAuthService $oauthService;

    public function __construct(MercadoLibreOAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeToken(Request $request): JsonResponse
    {
        try {
            // Validate required parameters
            $request->validate([
                'grant_type' => 'required|string',
                'client_id' => 'required|string',
                'client_secret' => 'required|string',
                'code' => 'required|string',
                'redirect_uri' => 'required|string',
                'code_verifier' => 'required|string',
            ]);

            $response = $this->oauthService->exchangeToken(
                $request->input('grant_type'),
                $request->input('client_id'),
                $request->input('client_secret'),
                $request->input('code'),
                $request->input('redirect_uri'),
                $request->input('code_verifier')
            );

            // Save token to database
            $data = $request->only(['grant_type', 'client_id', 'client_secret', 'code', 'redirect_uri', 'code_verifier']);
            $token = OAuthToken::createFromOAuthResponse($response, $data);

            return response()->json([
                'success' => true,
                'message' => 'Token exchanged and saved successfully',
                'token_id' => $token->id,
                'user_id' => $token->user_id,
                'expires_at' => $token->expires_at,
                'status' => $token->status,
                'oauth_response' => $response
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'validation_error',
                'message' => 'Missing required parameters',
                'details' => $e->errors()
            ], 400);

        } catch (\Exception $e) {
            Log::error('OAuth token exchange failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'internal_error',
                'message' => 'Failed to exchange token'
            ], 500);
        }
    }

    /**
     * Get stored tokens
     */
    public function getTokens(Request $request): JsonResponse
    {
        try {
            $query = OAuthToken::query();

            // Filter by client_id if provided
            if ($request->has('client_id')) {
                $query->where('client_id', $request->input('client_id'));
            }

            // Filter by user_id if provided
            if ($request->has('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $tokens = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'tokens' => $tokens
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve tokens', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'internal_error',
                'message' => 'Failed to retrieve tokens'
            ], 500);
        }
    }

    /**
     * Get a specific token
     */
    public function getToken(int $id): JsonResponse
    {
        try {
            $token = OAuthToken::find($id);

            if (!$token) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'Token not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'token' => $token
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'internal_error',
                'message' => 'Failed to retrieve token'
            ], 500);
        }
    }

    /**
     * Revoke a token
     */
    public function revokeToken(int $id): JsonResponse
    {
        try {
            $token = OAuthToken::find($id);

            if (!$token) {
                return response()->json([
                    'error' => 'not_found',
                    'message' => 'Token not found'
                ], 404);
            }

            $token->revoke();

            return response()->json([
                'success' => true,
                'message' => 'Token revoked successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to revoke token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'internal_error',
                'message' => 'Failed to revoke token'
            ], 500);
        }
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): JsonResponse
    {
        try {
            $expiredTokens = OAuthToken::expired()->get();
            $count = $expiredTokens->count();

            foreach ($expiredTokens as $token) {
                $token->markAsExpired();
            }

            return response()->json([
                'success' => true,
                'message' => "Marked {$count} expired tokens",
                'count' => $count
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired tokens', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'internal_error',
                'message' => 'Failed to cleanup expired tokens'
            ], 500);
        }
    }
} 