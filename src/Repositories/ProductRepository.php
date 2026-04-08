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
        // ვიყენებთ SQL-ის ლოგიკას if-ის ნაცვლად: 
        // თუ :cat_name არის 'all', მაშინ WHERE პირობა ყოველთვის True-ა
        $sql = "SELECT p.*, c.name as cat_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE (:cat_name = 'all' OR LOWER(c.name) = LOWER(:cat_name))";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cat_name' => $categoryName]);
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // array_map უფრო კომპაქტურია ვიდრე foreach + ახალი მასივის შექმნა
        return array_map([$this, 'hydrateProduct'], $rows);
    }

    public function getProductById(string $id) {
        $sql = "SELECT p.*, c.name as cat_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // გამოიყენება მოკლე Ternary ოპერატორი
        return $row ? $this->hydrateProduct($row) : null;
    }

   private function hydrateProduct(array $row) {
    // გალერეა
    $galleryStmt = $this->db->prepare("SELECT image_url FROM gallery WHERE product_id = ?");
    $galleryStmt->execute([$row['id']]);
    $row['gallery'] = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);

    // Factory-ს გამოძახება (თუ cat_name აკლია, გადაეცემა 'clothes')
    $product = ProductFactory::create($row['cat_name'] ?? 'clothes', $row);

    // ფასები
    $priceStmt = $this->db->prepare("SELECT amount, currency_label, currency_symbol FROM prices WHERE product_id = ?");
    $priceStmt->execute([$row['id']]);
    while ($priceRow = $priceStmt->fetch(PDO::FETCH_ASSOC)) {
        $product->addPrice(new Price($priceRow));
    }

    // ატრიბუტები
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