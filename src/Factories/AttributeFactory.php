<?php
namespace App\Factories;

use App\Attributes\TextAttribute;
use App\Attributes\SwatchAttribute;

class AttributeFactory {
    private static array $classMap = [
        'text'   => TextAttribute::class,
        'swatch' => SwatchAttribute::class,
    ];

    public static function create(string $type, array $data) {
        $className = self::$classMap[$type] ?? TextAttribute::class;
        return new $className($data);
    }
}