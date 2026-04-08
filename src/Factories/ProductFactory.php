<?php
namespace App\Factories;

use App\Models\TechProduct;
use App\Models\ClothesProduct;

class ProductFactory {
    private static array $classMap = [
        'tech'    => TechProduct::class,
        'clothes' => ClothesProduct::class,
    ];

    public static function create(string $category, array $data) {
        $className = self::$classMap[$category] ?? TechProduct::class;
        return new $className($data);
    }
}