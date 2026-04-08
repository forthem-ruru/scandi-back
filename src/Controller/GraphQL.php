<?php
namespace App\Controller;

use App\GraphQL\SchemaBuilder;
use GraphQL\GraphQL as GraphQLBase;

class GraphQL {
    public static function handle() {
        
        // 1. CORS Header-ები - აუცილებელია ფრონტენდთან კავშირისთვის
        // ნება დართე ნებისმიერ დომენს (შეგიძლია შეცვალო შენი კონკრეტული URL-ით)
     header("Access-Control-Allow-Origin: *"); 
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    
    // 2. Preflight მოთხოვნის (OPTIONS) დახურვა
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit; 
    }

    header('Content-Type: application/json; charset=UTF-8');

        try {
            // 3. შემოსული მონაცემების წაკითხვა
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            $query = $input['query'] ?? null;
            $variables = $input['variables'] ?? null;

            // თუ Query არ არის (მაგალითად, ბრაუზერიდან პირდაპირ გახსნისას)
            if (!$query) {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    return json_encode([
                        'status' => 'success',
                        'message' => 'GraphQL API is running. Please use POST request.'
                    ]);
                }
                throw new \Exception("No GraphQL query provided.");
            }

            // 4. GraphQL სქემის აწყობა და გაშვება
            $schema = SchemaBuilder::build();
            $result = GraphQLBase::executeQuery(
                $schema, 
                $query, 
                null, 
                null, 
                $variables
            );
            
            $output = $result->toArray();

        } catch (\Throwable $e) {
            // შეცდომების ფორმატირება GraphQL-ის სტანდარტით
            $output = [
                'errors' => [
                    [
                        'message' => $e->getMessage()
                    ]
                ]
            ];
        }

        // 5. პასუხის დაბრუნება JSON ფორმატში
        return json_encode($output);
    }
}