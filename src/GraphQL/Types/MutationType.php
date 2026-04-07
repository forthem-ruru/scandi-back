<?php
namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use App\Database;

class MutationType extends ObjectType {
    public function __construct() {
        $db = Database::getConnection();

        $attributeInput = new InputObjectType([
            'name' => 'AttributeInput',
            'fields' => [
                'name' => Type::nonNull(Type::string()),
                'value' => Type::nonNull(Type::string()),
            ]
        ]);

        $orderItemInput = new InputObjectType([
            'name' => 'OrderItemInput',
            'fields' => [
                'product_id' => Type::nonNull(Type::string()),
                'quantity' => Type::nonNull(Type::int()),
                'selected_attributes' => Type::listOf($attributeInput)
            ]
        ]);

        parent::__construct([
            'name' => 'Mutation',
            'fields' => [
                'createOrder' => [
                    'type' => new ObjectType([
                        'name' => 'OrderResponse',
                        'fields' => ['id' => ['type' => Type::string()]]
                    ]),
                    'args' => [
                        'items' => Type::nonNull(Type::listOf($orderItemInput))
                    ],
                    'resolve' => function($root, $args) use ($db) {
                        try {
                            $db->beginTransaction();
                            
                            $stmt = $db->prepare("INSERT INTO orders (created_at) VALUES (NOW())");
                            $stmt->execute();
                            $orderId = $db->lastInsertId();

                            $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, selected_attributes) VALUES (?, ?, ?, ?)");

                            foreach ($args['items'] as $item) {
                                $itemStmt->execute([
                                    $orderId,
                                    $item['product_id'],
                                    $item['quantity'],
                                    json_encode($item['selected_attributes'])
                                ]);
                            }

                            $db->commit();
                            return ['id' => (string)$orderId];
                        } catch (\Exception $e) {
                            if ($db->inTransaction()) $db->rollBack();
                            throw new \Exception("Database Error: " . $e->getMessage());
                        }
                    }
                ]
            ]
        ]);
    }
}