<?php
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class AttributeType extends ObjectType {
    public function __construct() {
        parent::__construct([
            'name' => 'Attribute',
            'fields' => [
    'id' => [
        'type' => Type::string(),
        'resolve' => fn($attr) => $attr->getId() 
    ],
    'name' => [
        'type' => Type::string(),
        'resolve' => fn($attr) => $attr->getName() 
    ],
    'type' => [
        'type' => Type::string(),
        'resolve' => fn($attr) => $attr->getType()
    ],
    'items' => [
        'type' => Type::listOf(new AttributeItemType()),
        'resolve' => fn($attr) => $attr->getItems()
    ],
]
        ]);
    }
}