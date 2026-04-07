<?php
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class AttributeInputType extends InputObjectType {
    public function __construct() {
        parent::__construct([
            'name' => 'AttributeInput',
            'fields' => [
                'id'    => ['type' => Type::nonNull(Type::string())],
                'value' => ['type' => Type::nonNull(Type::string())],
            ]
        ]);
    }
}