<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\ProductUnit;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $products = $this->getProducts();

        $this->initializeTestData($products);

        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }

    public function getProducts(): array
    {
        return [
            [
                'name' => 'Burger',
                'ingredients' => [
                    [
                        'name' => 'Beef',
                        'stock' => 20000,
                        'initial_stock' => 20000,
                        'unit' => ProductUnit::GRAM,
                        'quantity' => 150,
                        'quantity_unit' => ProductUnit::GRAM,
                    ],
                    [
                        'name' => 'Cheese',
                        'stock' => 5000,
                        'initial_stock' => 5000,
                        'unit' => ProductUnit::GRAM,
                        'quantity' => 30,
                        'quantity_unit' => ProductUnit::GRAM,
                    ],
                    [
                        'name' => 'Onion',
                        'stock' => 1000,
                        'initial_stock' => 1000,
                        'unit' => ProductUnit::GRAM,
                        'quantity' => 20,
                        'quantity_unit' => ProductUnit::GRAM,
                    ],
                ],
            ],
            [
                'name' => 'Pizza',
                'ingredients' => [
                    [
                        'name' => 'Tomato',
                        'stock' => 10000,
                        'initial_stock' => 10000,
                        'unit' => ProductUnit::GRAM,
                        'quantity' => 80,
                        'quantity_unit' => ProductUnit::GRAM,
                    ],
                    [
                        'name' => 'Cheese',
                        'stock' => 5000,
                        'initial_stock' => 5000,
                        'unit' => ProductUnit::GRAM,
                        'quantity' => 30,
                        'quantity_unit' => ProductUnit::GRAM,
                    ],
                ],
            ],
        ];
    }

    public function initializeTestData(array $products): void
    {
        foreach ($products as $product) {
            $productModel = Product::factory()->create([
                'name' => $product['name'],
            ]);

            foreach ($product['ingredients'] as $ingredient) {
                $ingredientIsExisting = Ingredient::query()
                    ->where('name', $ingredient['name'])
                    ->first();

                if ($ingredientIsExisting) {
                    continue;
                }

                $ingredientModel = Ingredient::factory()->create([
                    'name' => $ingredient['name'],
                    'stock' => $ingredient['stock'],
                    'initial_stock' => $ingredient['initial_stock'],
                    'unit' => $ingredient['unit'],
                ]);

                $productModel->ingredients()->attach($ingredientModel, [
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['quantity_unit'],
                ]);
            }
        }
    }
}
