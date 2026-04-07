<?php

namespace App\Attributes;


class SwatchAttribute extends Attribute {
    public function getDisplayData(): array {
   
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => 'swatch',
            'items' => $this->items
        ];
    }
}