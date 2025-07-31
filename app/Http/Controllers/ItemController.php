<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MercadoLibreOAuthService;
use App\Helpers\OAuthHelper;
use App\Helpers\ItemsHelper;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{

    protected MercadoLibreOAuthService $oauthService;

    public function __construct(MercadoLibreOAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    public function getItemCategory(){

        $access_token = OAuthHelper::getActiveToken(config('services.mercadolibre.client_id'));
        $item = ItemsHelper::getItem();
        $item_title = $item['title'];

        $response = 
            $this->oauthService->makeApiRequest(
                'sites/MLB/domain_discovery/search',
                $access_token,
                'GET',
                [
                    'q' => $item_title
                ]
            );
        dd($response);
    }

    public function validateItem(Request $request)
    {
        try{
            $access_token = OAuthHelper::getActiveToken(config('services.mercadolibre.client_id'));
            $itemData = ItemsHelper::getItem();
            
            $response = $this->oauthService->makeApiRequest(
                'items/validate',
                $access_token,
                'POST',
                $itemData
            );
            dd($response);
        } catch (\Exception $e) {
            Log::error('Failed to create test user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            dd($e->getMessage());
            return response()->json([
                'error' => 'api_error',
                'message' => 'Failed to create test user: ' . $e->getMessage()
            ], 500);
        }
    }
}
