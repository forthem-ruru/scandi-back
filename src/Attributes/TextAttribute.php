<?php

namespace App\Attributes;

class TextAttribute extends Attribute {
    public function getDisplayData(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => 'text',
            'items' => $this->items
        ];
    }
}