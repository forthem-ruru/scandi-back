<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Database;

$json = file_get_contents(__DIR__ . '/data.json');
$db = Database::getConnection();
$data = json_decode($json, true);

try {
    $db->beginTransaction();

  
    $categoryMap = [];
    $stmt = $db->query("SELECT id, name FROM categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categoryMap[$row['name']] = $row['id'];
    }

    $products = $data['data']['products'];

    foreach ($products as $p) {
    
        $stmt = $db->prepare("INSERT IGNORE INTO products (id, name, in_stock, description, category_id, brand) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $p['id'],
            $p['name'],
            $p['inStock'] ? 1 : 0,
            $p['description'],
            $categoryMap[$p['category']],
            $p['brand']
        ]);

    
        foreach ($p['gallery'] as $imageUrl) {
            $stmt = $db->prepare("INSERT INTO gallery (product_id, image_url) VALUES (?, ?)");
            $stmt->execute([$p['id'], $imageUrl]);
        }

     
        foreach ($p['prices'] as $price) {
            $stmt = $db->prepare("INSERT INTO prices (product_id, amount, currency_label, currency_symbol) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $p['id'],
                $price['amount'],
                $price['currency']['label'],
                $price['currency']['symbol']
            ]);
        }

 
        foreach ($p['attributes'] as $attr) {
        
            $attributeSetId = $p['id'] . '_' . str_replace(' ', '_', $attr['id']);
            
            $stmt = $db->prepare("INSERT IGNORE INTO attributes (id, product_id, name, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$attributeSetId, $p['id'], $attr['name'], $attr['type']]);

     
            foreach ($attr['items'] as $item) {
                $stmt = $db->prepare("INSERT INTO attribute_items (attribute_id, display_value, value) VALUES (?, ?, ?)");
                $stmt->execute([$attributeSetId, $item['displayValue'], $item['value']]);
            }
        }
        echo "Imported product: " . $p['name'] . "\n";
    }

    $db->commit();
    echo "\nFull import completed successfully!";

} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage();
}