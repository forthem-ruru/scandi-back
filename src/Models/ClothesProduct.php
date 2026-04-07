<?php
namespace App\Models;

class ClothesProduct extends AbstractProduct {
    public function getType() {
        return 'clothes';
    }
}