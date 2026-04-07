<?php
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use App\GraphQL\SchemaBuilder;
use App\Repositories\ProductRepository;

class QueryType extends ObjectType {
    public function __construct() {
        parent::__construct([
            'name' => 'Query',
            'fields' => [
                'products' => [
                    'type' => Type::listOf(SchemaBuilder::getType(ProductType::class)),
                    'args' => ['category' => Type::string()],
                    'resolve' => function($root, $args) {
                        return (new ProductRepository())->getProductsByCategory($args['category'] ?? 'all');
                    }
                ],
                'product' => [
                    'type' => SchemaBuilder::getType(ProductType::class),
                    'args' => ['id' => Type::nonNull(Type::string())],
                    'resolve' => function($root, $args) {
                        return (new ProductRepository())->getProductById($args['id']);
                    }
                ],
                'categories' => [
                    'type' => Type::listOf(new ObjectType([
                        'name' => 'Category',
                        'fields' => ['name' => ['type' => Type::string()]]
                    ])),
                    'resolve' => fn() => [['name' => 'all'], ['name' => 'clothes'], ['name' => 'tech']]
                ]
            ]
        ]);
    }
}