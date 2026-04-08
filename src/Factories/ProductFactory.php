<?php
namespace App\Factories;

use App\Models\TechProduct;
use App\Models\ClothesProduct;

class ProductFactory {
    private static array $classMap = [
        'tech'    => TechProduct::class,
        'clothes' => ClothesProduct::class,
    ];

  public static function create(?string $category, array $data) {
    // თუ კატეგორია NULL-ია ან არ არის მეპინგში, ვიყენებთ 'clothes'-ს როგორც დეფოლტს
    $categoryKey = strtolower($category ?? 'clothes');
    
    $className = self::$classMap[$categoryKey] ?? ClothesProduct::class;
    return new $className($data);
    }
}