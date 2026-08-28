<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $sentAt = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'body' => fake()->paragraph(),
            'read_at' => fake()->boolean(70)
                ? (clone $sentAt)->modify('+'.fake()->numberBetween(5, 120).' minutes')
                : null,
            'created_at' => $sentAt,
            'updated_at' => $sentAt,
        ];
    }

    public function unread(): static
    {
        return $this->state(['read_at' => null]);
    }

    public function read(): static
    {
        return $this->state(function (array $attrs) {
            $sent = $attrs['created_at'];

            return [
                'read_at' => (clone $sent)->modify('+15 minutes'),
            ];
        });
    }
}
