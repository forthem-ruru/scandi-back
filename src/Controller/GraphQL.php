<?php
namespace App\Controller;

use App\GraphQL\SchemaBuilder;
use GraphQL\GraphQL as GraphQLBase;

class GraphQL {
    public static function handle() {
        // ნება დართე ნებისმიერ Origin-ს (CORS Fix)
        header("Access-Control-Allow-Origin: *"); 
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header('Content-Type: application/json; charset=UTF-8');

        // OPTIONS მოთხოვნის დამუშავება
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit; // დაუყოვნებლივ შეწყვიტე მუშაობა
        }

        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            $query = $input['query'] ?? null;
            $variables = $input['variables'] ?? null;

            if (!$query) {
                // თუ ვინმე ბრაუზერიდან უბრალოდ გახსნის ლინკს
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    return json_encode(['message' => 'GraphQL API is running...']);
                }
                throw new \Exception("No query provided.");
            }

            $schema = SchemaBuilder::build();
            $result = GraphQLBase::executeQuery($schema, $query, null, null, $variables);
            $output = $result->toArray();
        } catch (\Throwable $e) {
            $output = ['errors' => [['message' => $e->getMessage()]]];
        }

        return json_encode($output);
    }
}