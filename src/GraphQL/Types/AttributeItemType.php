<?php
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class AttributeItemType extends ObjectType {
    public function __construct() {
        parent::__construct([
            'name' => 'AttributeItem',
          'fields' => [
    'id' => Type::string(),
    'displayValue' => [
        'type' => Type::string(),
    
        'resolve' => fn($item) => $item['display_value'] ?? $item['value'] 
    ],
    'value' => [
        'type' => Type::string(),
        'resolve' => fn($item) => $item['value']
    ]
]
        ]);
    }
}