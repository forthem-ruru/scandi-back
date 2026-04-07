<?php
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class OrderItemInputType extends InputObjectType {
    public function __construct() {
        parent::__construct([
            'name' => 'OrderItemInput',
            'fields' => [
                'product_id' => ['type' => Type::nonNull(Type::string())],
                'quantity'   => ['type' => Type::nonNull(Type::int())],
                'selected_attributes' => [
                    'type' => Type::listOf(new AttributeInputType())
                ]
            ]
        ]);
    }
}