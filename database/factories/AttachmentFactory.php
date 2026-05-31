<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = $this->faker->randomElement(['pdf', 'png', 'jpg', 'txt', 'csv']);
        $filename = $this->faker->slug(3).'.'.$extension;

        return [
            'attachable_type' => (new Ticket)->getMorphClass(),
            'attachable_id' => Ticket::factory(),
            'uploaded_by' => User::factory(),
            'filename' => $filename,
            'path' => 'attachments/'.$this->faker->uuid().'/'.$filename,
            'mime_type' => $this->faker->mimeType(),
            'size_bytes' => $this->faker->numberBetween(1024, 10_485_760),
        ];
    }
}
