<?php
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

use App\GraphQL\Types\PriceType;
use App\GraphQL\Types\AttributeType;

class ProductType extends ObjectType {
    public function __construct() {
        parent::__construct([
            'name' => 'Product',
            'fields' => fn() => [
                'id' => [
                    'type' => Type::string(),
                    'resolve' => fn($product) => $product->getId()
                ],
                'name' => [
                    'type' => Type::string(),
                    'resolve' => fn($product) => $product->getName() 
                ],
                'brand' => [
                    'type' => Type::string(),
                    'resolve' => fn($product) => $product->getBrand()
                ],
                'description' => [
                    'type' => Type::string(),
                    'resolve' => fn($product) => $product->getDescription()
                ],
                'inStock' => [
                    'type' => Type::boolean(),
                    'resolve' => fn($product) => (bool)$product->getInStock()
                ],
                'prices' => [
                    'type' => Type::listOf(new PriceType()),
                    'resolve' => fn($product) => $product->getPrices() 
                ],
                'attributes' => [
                    'type' => Type::listOf(new AttributeType()),
                    'resolve' => fn($product) => $product->getAttributes()
                ],
                'gallery' => [
    'type' => Type::listOf(Type::string()),
    'resolve' => fn($product) => $product->getGallery() 
],
            ]
        ]);
    }
}