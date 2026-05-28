<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 10000);

        return [
            'invoice_number' => 'INV-2026-'.$this->faker->unique()->numberBetween(1, 999999),
            'client_id' => Client::factory(),
            'status' => InvoiceStatus::Draft,
            'issued_at' => null,
            'due_at' => null,
            'paid_at' => null,
            'subtotal' => $subtotal,
            'tax_rate' => null,
            'tax_amount' => null,
            'total' => $subtotal,
            'notes' => null,
            'terms' => null,
        ];
    }
}
