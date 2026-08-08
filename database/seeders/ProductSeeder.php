<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;

        foreach ($this->planifoliaGrades() as $grade) {
            $this->createProduct('Vanilla Planifolia', 'Vanilla planifolia Andrews', 'raw', $grade, $sort++);
        }

        foreach ($this->tahitensisGrades() as $grade) {
            $this->createProduct('Vanilla Tahitensis', 'Vanilla tahitensis J.W. Moore', 'raw', $grade, $sort++);
        }

        foreach ($this->valueAddedProducts() as $product) {
            $this->createProduct($product['name'], null, 'value_added', $product, $sort++);
        }
    }

    private function createProduct(string $name, ?string $species, string $category, array $attributes, int $sort): void
    {
        $product = Product::updateOrCreate(
            ['name' => $name . (isset($attributes['grade']) ? " - {$attributes['grade']}" : '')],
            [
                'species' => $species,
                'category' => $category,
                'default_unit' => 'KG',
                'default_country_of_origin' => 'Indonesia',
                'is_active' => true,
                'sort_order' => $sort,
            ]
        );

        $product->productAttributes()->delete();

        $order = 0;
        foreach ($attributes as $key => $value) {
            if ($key === 'name') {
                continue;
            }
            $product->productAttributes()->create([
                'key' => $key,
                'value' => $value,
                'sort_order' => $order++,
            ]);
        }
    }

    private function planifoliaGrades(): array
    {
        return [
            [
                'grade' => 'Gourmet Premium', 'size' => '16-18 cm', 'weight' => '4-6 gr',
                'moisture' => '30-35%', 'packaging' => 'Vacuum Plastic',
                'condition' => 'Oily, full-bodied, non-split, flexible, glossy',
                'aroma' => 'Intense, balsamic-sweet',
            ],
            [
                'grade' => 'Grade A Premium', 'size' => '16-18 cm', 'weight' => '3-5 gr',
                'moisture' => '25-30%', 'packaging' => 'Vacuum Plastic',
                'condition' => 'Oily, slightly firm, non-split, dark brown',
                'aroma' => 'Sweet, balanced, smooth',
            ],
            [
                'grade' => 'Grade B Premium', 'size' => '15-20 cm',
                'moisture' => '15-20%', 'packaging' => 'Vacuum Plastic',
                'condition' => 'Semi-dry, minor splits allowed',
                'aroma' => 'Mild, ideal for extraction',
            ],
            [
                'grade' => 'Grade C Premium', 'size' => 'All size',
                'moisture' => 'Natural/Low', 'packaging' => 'Bulk/Plastic (Non Vacuum)',
                'condition' => 'Broken, dry, split, for mass extraction',
                'aroma' => 'Minimal vanilla scent',
            ],
            [
                'grade' => 'Gourmet Super Premium', 'size' => '19-21 cm', 'weight' => '5-8 gr',
                'moisture' => '30-38%', 'packaging' => 'Vacuum Plastic',
                'condition' => 'Oily, full-bodied, non-split, flexible, glossy',
                'aroma' => 'Intense, balsamic-sweet, warm',
            ],
            [
                'grade' => 'Grade A Super Premium', 'size' => '19-21 cm', 'weight' => '4-7 gr',
                'moisture' => '30-38%', 'packaging' => 'Vacuum Plastic',
                'condition' => 'Oily, slightly firm, non-split, dark brown',
                'aroma' => 'Sweet, balanced, smooth',
            ],
        ];
    }

    private function tahitensisGrades(): array
    {
        return [
            [
                'grade' => 'Gourmet Premium', 'size' => '13-16 cm', 'weight' => '3-6 gr',
                'moisture' => '27-32%', 'packaging' => 'Vacuum Plastic',
                'condition' => 'Oily, glossy surface with no splits, soft and smooth',
                'aroma' => 'Intensely sweet, exotic floral with rich fruity notes',
            ],
            [
                'grade' => 'Grade A Premium', 'size' => '14-16 cm', 'weight' => '3-6 gr',
                'moisture' => '25-30%', 'packaging' => 'Vacuum Plastic',
                'condition' => 'Oily, semi-firm, soft, light wrinkling',
                'aroma' => 'Sweet floral with subtle fruitiness',
            ],
            [
                'grade' => 'Grade B Premium', 'size' => '13-16 cm', 'weight' => '3-5 gr',
                'moisture' => '15-20%', 'packaging' => 'Vacuum Plastic',
                'condition' => 'Semi-dry, flexible but less oily, some splits',
                'aroma' => 'Light floral, fruity undertones',
            ],
            [
                'grade' => 'Grade C Premium', 'size' => 'All size', 'weight' => '3-5 gr',
                'moisture' => 'Natural/Low', 'packaging' => 'Bulk/Plastic (Non Vacuum)',
                'condition' => 'Dry, brittle, split allowed, rough surface',
                'aroma' => 'Light floral with weak vanilla aroma',
            ],
        ];
    }

    private function valueAddedProducts(): array
    {
        return [
            [
                'name' => 'Vanilla Caviar', 'size' => '1 KG',
                'description' => 'Pure premium vanilla seeds extracted from mature vanilla pods, with strong aroma suitable for gourmet applications.',
            ],
            [
                'name' => 'Crystallized Vanilla', 'size' => '1 KG',
                'description' => 'Naturally crystallized vanilla pods with aromatic vanillin crystals.',
            ],
            [
                'name' => 'Vanilla Powder (100%)', 'size' => '1 KG',
                'description' => 'Fine powder made from premium dried vanilla pods.',
            ],
            [
                'name' => 'Vanilla Paste (Natural)', 'size' => '1 KG',
                'description' => 'Pure natural vanilla paste containing authentic vanilla seeds.',
            ],
            [
                'name' => 'Vanilla Extract Alcohol (Natural)', 'size' => '1 KG',
                'description' => 'Natural vanilla extract with alcohol.',
            ],
            [
                'name' => 'Vanilla Extract Non-Alcohol (Natural)', 'size' => '1 KG',
                'description' => 'Natural non-alcoholic vanilla extract.',
            ],
            [
                'name' => 'Vanilla Essence', 'size' => '1 KG',
                'description' => 'Concentrated vanilla flavor formulation for food and beverage applications.',
            ],
        ];
    }
}
