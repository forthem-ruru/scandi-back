<?php

require_once __DIR__ . '/vendor/autoload.php';

// .env ფაილის ჩატვირთვა (აუცილებელია getenv()-ისთვის)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); 

use App\Database;

// მონაცემების წაკითხვა JSON ფაილიდან
$jsonPath = __DIR__ . '/data.json';
if (!file_exists($jsonPath)) {
    die("Error: data.json file not found at $jsonPath");
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true);

try {
    // ბაზასთან კავშირის დამყარება
    $db = Database::getConnection();
    
    $db->beginTransaction();

    // კატეგორიების რუკის (Map) შექმნა ID-ების ამოსაღებად
    $categoryMap = [];
    $stmt = $db->query("SELECT id, name FROM categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categoryMap[$row['name']] = $row['id'];
    }

    $products = $data['data']['products'] ?? [];

    foreach ($products as $p) {
        // 1. პროდუქტის ჩამატება
        $stmt = $db->prepare("INSERT IGNORE INTO products (id, name, in_stock, description, category_id, brand) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $p['id'],
            $p['name'],
            $p['inStock'] ? 1 : 0,
            $p['description'],
            $categoryMap[$p['category']] ?? null,
            $p['brand']
        ]);

        // 2. გალერეის სურათების ჩამატება
        if (isset($p['gallery'])) {
            foreach ($p['gallery'] as $imageUrl) {
                $stmt = $db->prepare("INSERT INTO gallery (product_id, image_url) VALUES (?, ?)");
                $stmt->execute([$p['id'], $imageUrl]);
            }
        }

        // 3. ფასების ჩამატება
        if (isset($p['prices'])) {
            foreach ($p['prices'] as $price) {
                $stmt = $db->prepare("INSERT INTO prices (product_id, amount, currency_label, currency_symbol) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $p['id'],
                    $price['amount'],
                    $price['currency']['label'],
                    $price['currency']['symbol']
                ]);
            }
        }

        // 4. ატრიბუტების და მათი მნიშვნელობების ჩამატება
        if (isset($p['attributes'])) {
            foreach ($p['attributes'] as $attr) {
                // უნიკალური ID ატრიბუტისთვის
                $attributeSetId = $p['id'] . '_' . str_replace(' ', '_', $attr['id']);
                
                $stmt = $db->prepare("INSERT IGNORE INTO attributes (id, product_id, name, type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$attributeSetId, $p['id'], $attr['name'], $attr['type']]);

                foreach ($attr['items'] as $item) {
                    $stmt = $db->prepare("INSERT INTO attribute_items (attribute_id, display_value, value) VALUES (?, ?, ?)");
                    $stmt->execute([$attributeSetId, $item['displayValue'], $item['value']]);
                }
            }
        }
        echo "Imported product: " . $p['name'] . "\n";
    }

    $db->commit();
    echo "\nFull import completed successfully! 🚀\n";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage();
}