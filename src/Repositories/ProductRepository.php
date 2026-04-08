<?php

namespace App\Repositories;

use App\Database;
use App\Factories\ProductFactory;
use App\Factories\AttributeFactory; 
use App\Models\Price;               
use PDO;

class ProductRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getProductsByCategory(string $categoryName): array {
        // 1. ვიყენებთ LEFT JOIN-ს. თუ category_id პროდუქტებში NULL-ია, 
        // პროდუქტი მაინც წამოვა და არ "დაიკარგება".
        $sql = "SELECT p.*, c.name as cat_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id";

        if ($categoryName !== 'all') {
            $sql .= " WHERE c.name = :cat_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['cat_name' => $categoryName]);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // თუ ბაზამ საერთოდ არაფერი დააბრუნა
        if (empty($rows)) {
            return [];
        }

        $productObjects = [];

        foreach ($rows as $row) {
            // თუ cat_name ცარიელია (LEFT JOIN-ის გამო), მივცეთ default მნიშვნელობა,
            // რომ ProductFactory-მ შეძლოს ობიექტის შექმნა.
            if (empty($row['cat_name'])) {
                $row['cat_name'] = 'clothes'; 
            }

            try {
                $productObjects[] = $this->hydrateProduct($row);
            } catch (\Exception $e) {
                // თუ რომელიმე პროდუქტის დამუშავებისას კოდი "კვდება", 
                // ვაგრძელებთ შემდეგზე, რომ მთლიანი პასუხი არ გაფუჭდეს.
                continue;
            }
        }

        return $productObjects;
    }


    public function getProductById(string $id) {
        $sql = "SELECT p.*, c.name as cat_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        if (empty($row['cat_name'])) {
            $row['cat_name'] = 'clothes';
        }

        return $this->hydrateProduct($row);
    }

    private function hydrateProduct(array $row) {
        // გალერეის წამოღება
        $galleryStmt = $this->db->prepare("SELECT image_url FROM gallery WHERE product_id = ?");
        $galleryStmt->execute([$row['id']]);
        $row['gallery'] = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);

        // პროდუქტის ობიექტის შექმნა
        // მნიშვნელოვანია, რომ cat_name ემთხვეოდეს Factory-ს ქეისებს (clothes/tech)
        $product = ProductFactory::create(strtolower($row['cat_name']), $row);

        // ფასების წამოღება
        $priceStmt = $this->db->prepare("SELECT amount, currency_label, currency_symbol FROM prices WHERE product_id = ?");
        $priceStmt->execute([$row['id']]);
        while ($priceRow = $priceStmt->fetch(PDO::FETCH_ASSOC)) {
            $product->addPrice(new Price($priceRow));
        }

        // ატრიბუტების წამოღება
        $attrStmt = $this->db->prepare("SELECT id, name, type FROM attributes WHERE product_id = ?");
        $attrStmt->execute([$row['id']]);
        while ($attrRow = $attrStmt->fetch(PDO::FETCH_ASSOC)) {
            $itemStmt = $this->db->prepare("SELECT display_value, value FROM attribute_items WHERE attribute_id = ?");
            $itemStmt->execute([$attrRow['id']]);
            $attrRow['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

            $attributeObject = AttributeFactory::create($attrRow['type'], $attrRow);
            $product->addAttribute($attributeObject);
        }

        return $product;
    }
}