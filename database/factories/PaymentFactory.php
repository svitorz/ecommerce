<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'full_name' => $this->faker->name(),
            'card_last_four' => (string) fake()->numberBetween(1000, 9999),
            'card_expiration' => fake()->dateTimeBetween('now', '+5 years')->format('Y-m-01'),
        ];
    }
}
