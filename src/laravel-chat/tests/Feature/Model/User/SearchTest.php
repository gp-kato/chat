<?php

namespace Tests\Feature\Model\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;

    public function test_search_not_joined_returns_users_not_in_excluded_ids(): void
    {
        // 検索対象
        $matchedUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 除外されるユーザー
        $excludedUser = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
        ]);

        $excludedIds = [$excludedUser->id];

        $results = User::searchNotJoined('test', $excludedIds);

        $this->assertTrue($results->contains($matchedUser));
        $this->assertFalse($results->contains($excludedUser));
    }

    public function test_search_with_username(): void
    {
        // 検索対象
        $matchedUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 除外されるユーザー
        $excludedUser = User::factory()->create([
            'name' => 'Test Admin',
        ]);

        $excludedIds = [$excludedUser->id];

        $results = User::searchNotJoined('test', $excludedIds);

        $this->assertTrue($results->contains($matchedUser));
        $this->assertFalse($results->contains($excludedUser));
    }

    public function test_search_with_email(): void
    {
        // 検索対象
        $matchedUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 除外されるユーザー
        $excludedUser = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $excludedIds = [$excludedUser->id];

        $results = User::searchNotJoined('test', $excludedIds);

        $this->assertTrue($results->contains($matchedUser));
        $this->assertFalse($results->contains($excludedUser));
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $user = User::factory()->create([
            'name' => '100% User',
            'email' => 'percent@example.com',
        ]);

        $results = User::searchNotJoined('%', []);

        $this->assertFalse($results->contains($user));
    }
}
