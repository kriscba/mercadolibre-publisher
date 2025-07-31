<?php   

namespace App\Helpers;

use App\Models\Item;
use App\Services\MercadoLibreOAuthService;
use App\Helpers\OAuthHelper;

class ItemsHelper
{
    
    public static function getItem(){
        return $itemData = [
            "title" => "Vaso de plástico Messi",
            "category_id" => "MLA1902",
            "price" => 10000,
            "currency_id" => "ARS",
            "available_quantity" => 1,
            "buying_mode" => "buy_it_now",
            "listing_type_id" => "bronze",
            "condition" => "new",
            "description" => "Item:, Teacup Model: 1. Size: 5cm. Color: White. New in Box",
            "video_id" => "",
            "pictures" => [
                [
                    "source" => "https://www.google.com/images/branding/googlelogo/1x/googlelogo_color_272x92dp.png"
                ]
            ]
        ];
    }
    public static function getItemWithVariations(){
        return $itemData = [
            "title" => "Short",
            "category_id" => "MLA126455",
            "price" => 10,
            "currency_id" => "ARS",
            "buying_mode" => "buy_it_now",
            "listing_type_id" => "bronze",
            "condition" => "new",
            "description" => "Short with variations",
            "variations" => [
                [
                    "attribute_combinations" => [
                        [
                            "id" => "93000",
                            "value_id" => "101993"
                        ],
                        [
                            "id" => "83000",
                            "value_id" => "91993"
                        ]
                    ],
                    "available_quantity" => 1,
                    "price" => 10,
                    "picture_ids" => [
                        "http://bttpadel.es/image/cache/data/ARTICULOS/PROVEEDORES/BTTPADEL/BERMUDA%20ROJA-240x240.jpg"
                    ]
                ],
                [
                    "attribute_combinations" => [
                        [
                            "id" => "93000",
                            "value_id" => "101995"
                        ],
                        [
                            "id" => "83000",
                            "value_id" => "92013"
                        ]
                    ],
                    "available_quantity" => 1,
                    "price" => 10,
                    "picture_ids" => [
                        "http://www.forumsport.com/img/productos/299x299/381606.jpg"
                    ]
                ]
            ]
        ];
    }
}