<?php

require_once __DIR__ . '/vendor/autoload.php';

// .env ფაილის ჩატვირთვა
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); 

use App\Database;

// მონაცემების წაკითხვა JSON ფაილიდან
$jsonPath = __DIR__ . '/data.json';
if (!file_exists($jsonPath)) {
    die("Error: data.json file not found at $jsonPath \n");
}

$json = file_get_contents($jsonPath);
$data = json_decode($json, true);
$products = $data['data']['products'] ?? [];

try {
    $db = Database::getConnection();
    $db->beginTransaction();

    // --- ნაბიჯი 1: კატეგორიების შექმნა ---
    // ჯერ ამოვიღოთ ყველა უნიკალური კატეგორია JSON-იდან
    $jsonCategories = array_unique(array_column($products, 'category'));
    // ყოველთვის დავამატოთ 'all' კატეგორიაც ყოველი შემთხვევისთვის
    if (!in_array('all', $jsonCategories)) {
        $jsonCategories[] = 'all';
    }

    foreach ($jsonCategories as $catName) {
        $stmt = $db->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
        $stmt->execute([$catName]);
    }
    echo "Categories prepared... \n";

    // --- ნაბიჯი 2: კატეგორიების ID-ების წამოღება (Mapping) ---
    $categoryMap = [];
    $stmt = $db->query("SELECT id, name FROM categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categoryMap[$row['name']] = $row['id'];
    }

    // --- ნაბიჯი 3: პროდუქტების და მათი შვილობილი მონაცემების იმპორტი ---
    foreach ($products as $p) {
        // 1. პროდუქტის ჩამატება
        $stmt = $db->prepare("INSERT IGNORE INTO products (id, name, in_stock, description, category_id, brand) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $p['id'],
            $p['name'],
            $p['inStock'] ? 1 : 0,
            $p['description'],
            $categoryMap[$p['category']] ?? null, // ახლა უკვე ID ნამდვილად იარსებებს
            $p['brand']
        ]);

        // 2. გალერეის სურათების ჩამატება
        if (isset($p['gallery'])) {
            // წავშალოთ ძველი გალერეა დუბლიკატების თავიდან ასაცილებლად
            $db->prepare("DELETE FROM gallery WHERE product_id = ?")->execute([$p['id']]);
            foreach ($p['gallery'] as $imageUrl) {
                $stmt = $db->prepare("INSERT INTO gallery (product_id, image_url) VALUES (?, ?)");
                $stmt->execute([$p['id'], $imageUrl]);
            }
        }

        // 3. ფასების ჩამატება
        if (isset($p['prices'])) {
            $db->prepare("DELETE FROM prices WHERE product_id = ?")->execute([$p['id']]);
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

        // 4. ატრიბუტების ჩამატება
        if (isset($p['attributes'])) {
            foreach ($attr['items'] ?? [] as $item) {
                // წავშალოთ ძველი ატრიბუტები, რომ დუბლიკატი არ მოხდეს
                $db->prepare("DELETE FROM attribute_items WHERE attribute_id IN (SELECT id FROM attributes WHERE product_id = ?)")->execute([$p['id']]);
            }
            $db->prepare("DELETE FROM attributes WHERE product_id = ?")->execute([$p['id']]);

            foreach ($p['attributes'] as $attr) {
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
    echo "Error: " . $e->getMessage() . "\n";
}