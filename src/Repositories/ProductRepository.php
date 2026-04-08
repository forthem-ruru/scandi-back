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

    /**
     * აბრუნებს პროდუქტებს კატეგორიის მიხედვით.
     * LEFT JOIN უზრუნველყოფს, რომ პროდუქტი არ დაიკარგოს, 
     * თუ მისი კატეგორიის ID ბაზაში არასწორია.
     */
    public function getProductsByCategory(string $categoryName): array {
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
        $productObjects = [];

        foreach ($rows as $row) {
            // თუ კატეგორიის სახელი ვერ იპოვა, მივცეთ default 'all'
            if (empty($row['cat_name'])) {
                $row['cat_name'] = 'all'; 
            }
            $productObjects[] = $this->hydrateProduct($row);
        }

        return $productObjects;
    }

    /**
     * აბრუნებს კონკრეტულ პროდუქტს ID-ით
     */
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
            $row['cat_name'] = 'all';
        }

        return $this->hydrateProduct($row);
    }

    /**
     * აკავშირებს პროდუქტს გალერეასთან, ფასებთან და ატრიბუტებთან
     */
    private function hydrateProduct(array $row) {
        // გალერეის წამოღება
        $galleryStmt = $this->db->prepare("SELECT image_url FROM gallery WHERE product_id = ?");
        $galleryStmt->execute([$row['id']]);
        $row['gallery'] = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);

        // პროდუქტის ობიექტის შექმნა ფაქტორის მეშვეობით
        $product = ProductFactory::create($row['cat_name'], $row);

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