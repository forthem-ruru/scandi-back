<?php
namespace App\Controller;

use App\GraphQL\SchemaBuilder;
use GraphQL\GraphQL as GraphQLBase;

class GraphQL {
    public static function handle() {
  
        header("Access-Control-Allow-Origin: http://localhost:5173"); 
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header('Content-Type: application/json; charset=UTF-8');

      
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            return ''; 
        }

       
        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            $query = $input['query'] ?? null;
            $variables = $input['variables'] ?? null;

            if (!$query) {
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