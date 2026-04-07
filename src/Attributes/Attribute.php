<?php

namespace App\Attributes;

abstract class Attribute{
    protected $id;
    protected $name;
    protected $type;
    protected $items;

    public function __construct($data)
    {
      $this->id   = $data['id'] ?? null;
    $this->name = $data['name'] ?? null;
    $this->type = $data['type'] ?? null;
    $this->items = $data['items'] ?? [];
        }

        public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getItems() { return $this->items; }

    abstract public function getDisplayData(): array;

}