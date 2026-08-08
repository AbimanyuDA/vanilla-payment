<?php

// Currency code => [name, symbol]. Extend this list as needed; quotations store
// the raw ISO code so adding a new entry here does not affect existing records.
return [
    'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
    'EUR' => ['name' => 'Euro', 'symbol' => '€'],
    'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
    'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥'],
    'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$'],
    'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$'],
    'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥'],
    'IDR' => ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp'],
];
