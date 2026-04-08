<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Database;

$json = file_get_contents(__DIR__ . '/data.json');
$db = Database::getConnection();
$data = json_decode($json, true);

try {
    $db->beginTransaction();

    $products = $data['data']['products'];

    foreach ($products as $p) {
        // 1. კატეგორიის შემოწმება/დამატება
        $catName = $p['category'];
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$catName]);
        $categoryId = $stmt->fetchColumn();

        if (!$categoryId) {
            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$catName]);
            $categoryId = $db->lastInsertId();
        }

        // 2. პროდუქტის ჩაწერა
        $stmt = $db->prepare("INSERT IGNORE INTO products (id, name, in_stock, description, category_id, brand) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $p['id'],
            $p['name'],
            $p['inStock'] ? 1 : 0,
            $p['description'],
            $categoryId,
            $p['brand']
        ]);

        // 3. გალერეის ჩაწერა
        foreach ($p['gallery'] as $imageUrl) {
            $stmt = $db->prepare("INSERT INTO gallery (product_id, image_url) VALUES (?, ?)");
            $stmt->execute([$p['id'], $imageUrl]);
        }

        // 4. ფასების ჩაწერა
        foreach ($p['prices'] as $price) {
            $stmt = $db->prepare("INSERT INTO prices (product_id, amount, currency_label, currency_symbol) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $p['id'],
                $price['amount'],
                $price['currency']['label'],
                $price['currency']['symbol']
            ]);
        }

        // 5. ატრიბუტების ჩაწერა (ახალი ლოგიკა internal_id-ზე)
        foreach ($p['attributes'] as $attr) {
            // ვწერთ ატრიბუტს (id აქ არის მაგალითად 'Size')
            $stmt = $db->prepare("INSERT INTO attributes (id, product_id, name, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$attr['id'], $p['id'], $attr['name'], $attr['type']]);
            
            // ვიღებთ ბაზის მიერ მინიჭებულ internal_id-ს
            $attrInternalId = $db->lastInsertId();

            foreach ($attr['items'] as $item) {
                // ვაბამთ აითემს internal_id-ზე
                $stmt = $db->prepare("INSERT INTO attribute_items (attribute_id, display_value, value) VALUES (?, ?, ?)");
                $stmt->execute([$attrInternalId, $item['displayValue'], $item['value']]);
            }
        }
        echo "Imported product: " . $p['name'] . "\n";
    }

    $db->commit();
    echo "\nFull import completed successfully!";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage();
}