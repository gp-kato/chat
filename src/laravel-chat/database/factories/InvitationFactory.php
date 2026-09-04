<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'inviter_id' => User::factory(),
            'invitee_email' => fake()->unique()->safeEmail(),
            'token' => fake()->unique()->uuid(),
            'accepted_at' => null,
            'expires_at' => now()->addDays(31),
        ];
    }
}
