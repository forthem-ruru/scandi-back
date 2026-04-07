<?php

namespace App\Models;

class Price {
    public $amount;
    public $currencyLabel;
    public $currencySymbol;

    public function __construct(array $data){
        $this->amount = $data['amount'];

        $this->currencyLabel = $data['currency_label'];
        $this->currencySymbol = $data['currency_symbol'];
    }
}