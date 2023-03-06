<?php

namespace Database\Factories;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'stock' => fake()->randomNumber(),
            'initial_stock' => fake()->randomNumber(),
            'unit' => fake()->randomElement([
                ProductUnit::METER, ProductUnit::LITRE, ProductUnit::KG,
            ]),
        ];
    }
}
