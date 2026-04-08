<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); 

use App\Database;

$jsonPath = __DIR__ . '/data.json';
if (!file_exists($jsonPath)) {
    die("Error: data.json file not found \n");
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true);
$products = $data['data']['products'] ?? [];

try {
    $db = Database::getConnection();
    $db->beginTransaction();

    // 1. კატეგორიების მომზადება
    $jsonCategories = array_unique(array_column($products, 'category'));
    foreach ($jsonCategories as $catName) {
        $stmt = $db->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
        $stmt->execute([$catName]);
    }

    $categoryMap = [];
    $stmt = $db->query("SELECT id, name FROM categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categoryMap[$row['name']] = $row['id'];
    }

    // 2. პროდუქტების იმპორტი
    foreach ($products as $p) {
        // პროდუქტის ჩამატება
        $stmt = $db->prepare("INSERT IGNORE INTO products (id, name, in_stock, description, category_id, brand) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $p['id'],
            $p['name'],
            $p['inStock'] ? 1 : 0,
            $p['description'],
            $categoryMap[$p['category']] ?? null,
            $p['brand']
        ]);

        // გალერეა (დუბლიკატების გარეშე)
        $db->prepare("DELETE FROM gallery WHERE product_id = ?")->execute([$p['id']]);
        if (isset($p['gallery'])) {
            foreach ($p['gallery'] as $imageUrl) {
                $db->prepare("INSERT INTO gallery (product_id, image_url) VALUES (?, ?)")->execute([$p['id'], $imageUrl]);
            }
        }

        // ფასები (დუბლიკატების გარეშე)
        $db->prepare("DELETE FROM prices WHERE product_id = ?")->execute([$p['id']]);
        if (isset($p['prices'])) {
            foreach ($p['prices'] as $price) {
                $db->prepare("INSERT INTO prices (product_id, amount, currency_label, currency_symbol) VALUES (?, ?, ?, ?)")
                   ->execute([$p['id'], $price['amount'], $price['currency']['label'], $price['currency']['symbol']]);
            }
        }

        // ატრიბუტები
        if (isset($p['attributes'])) {
            // ჯერ ვშლით ძველ ატრიბუტებს, რომ ახლები სუფთად ჩაიწეროს
            $stmt = $db->prepare("SELECT id FROM attributes WHERE product_id = ?");
            $stmt->execute([$p['id']]);
            $existingAttrIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($existingAttrIds)) {
                $placeholders = implode(',', array_fill(0, count($existingAttrIds), '?'));
                $db->prepare("DELETE FROM attribute_items WHERE attribute_id IN ($placeholders)")->execute($existingAttrIds);
                $db->prepare("DELETE FROM attributes WHERE product_id = ?")->execute([$p['id']]);
            }

            foreach ($p['attributes'] as $attr) {
                $attributeSetId = $p['id'] . '_' . str_replace(' ', '_', $attr['id']);
                
                $db->prepare("INSERT IGNORE INTO attributes (id, product_id, name, type) VALUES (?, ?, ?, ?)")
                   ->execute([$attributeSetId, $p['id'], $attr['name'], $attr['type']]);

                foreach ($attr['items'] as $item) {
                    $db->prepare("INSERT INTO attribute_items (attribute_id, display_value, value) VALUES (?, ?, ?)")
                       ->execute([$attributeSetId, $item['displayValue'], $item['value']]);
                }
            }
        }
        echo "Imported: " . $p['name'] . "\n";
    }

    $db->commit();
    echo "\nFull import completed successfully! 🚀\n";

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}