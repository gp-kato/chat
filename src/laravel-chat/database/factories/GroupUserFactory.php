<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupUser>
 */
class GroupUserFactory extends Factory
{
    protected $model = GroupUser::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ];
    }

    public function member(): static
    {
        return $this->state([
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }

    public function admin(): static
    {
        return $this->state([
            'role' => 'admin',
        ]);
    }

    public function applicant(): static
    {
        return $this->state([
            'joined_at' => null,
            'role' => 'applicant',
        ]);
    }

    public function leftadmin(): static
    {
        return $this->state([
            'joined_at' => now()->subDays(2),
            'left_at' => now(),
            'role' => 'admin',
        ]);
    }

    public function leftuser(): static
    {
        return $this->state([
            'joined_at' => now()->subDays(2),
            'left_at' => now(),
            'role' => 'member',
        ]);
    }
}
