<?php
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class PriceType extends ObjectType {
    public function __construct() {
        parent::__construct([
            'name' => 'Price',
            'fields' => [
                'amount' => [
                    'type' => Type::float(),
                    'resolve' => fn($price) => $price->amount
                ],
                'currency_label' => [
        'type' => Type::string(),
    
        'resolve' => fn($price) => $price->currency_label ?? $price->label ?? 'USD' 
    ],
    'currency_symbol' => [
        'type' => Type::string(),
        'resolve' => fn($price) => $price->currency_symbol ?? $price->symbol ?? '$'
    ],
            ]
        ]);
    }
}