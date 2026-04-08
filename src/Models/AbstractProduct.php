<?php 
namespace App\Models;

use App\Models\Price;
use App\Models\Attribute; // დარწმუნდი რომ namespace სწორია

abstract class AbstractProduct {
    protected $id;
    protected $name;
    protected $inStock;
    protected $description;
    protected $brand;
    protected $category; // კატეგორიის სახელი
    protected array $gallery = [];
    protected array $prices = [];
    protected array $attributes = [];

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->inStock = $data['in_stock'];
        $this->description = $data['description'];
        $this->brand = $data['brand'];
        // თუ SQL-ში JOIN-ით ამოგაქვს კატეგორიის სახელი, ის აქ უნდა მიენიჭოს
        $this->category = $data['category_name'] ?? ($data['category'] ?? null);
        
        $this->gallery = isset($data['gallery']) ? 
            (is_array($data['gallery']) ? $data['gallery'] : json_decode($data['gallery'], true)) 
            : [];
    }

    public function getName() {
        return $this->name;
    }

    // აი ეს მეთოდი აკლდა GraphQL-სთვის
    public function getCategory() {
        return $this->category;
    }

    abstract public function getType();

    public function addPrice(Price $price){
        $this->prices[] = $price;
    }

    public function addAttribute($attribute){
        $this->attributes[] = $attribute;
    }

    public function getPrices(): array {
        return $this->prices;
    }

    public function getAttributes(): array {
        return $this->attributes;
    }

    public function getId() { return $this->id; }
    
    public function getBrand() { return $this->brand; }
    
    public function getInStock() { return $this->inStock; }
    
    public function getGallery(): array {
        return $this->gallery;
    }

    public function getDescription() {
        return $this->description;
    }
}

//აქააც