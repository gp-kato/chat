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

        $results = User::searchNotJoined('test', $excludedIds)->get();

        $this->assertTrue($results->contains('id', $matchedUser->id));
        $this->assertFalse($results->contains('id', $excludedUser->id));
    }

    public function test_search_hits_by_name_only(): void
    {
        // name にのみヒットするユーザー
        $matchedUser = User::factory()->create([
            'name'  => 'AlphaUser',
            'email' => 'no-match@example.com',
        ]);

        // 除外ユーザー（どちらにもヒットしない）
        $excludedUser = User::factory()->create([
            'name'  => 'BetaUser',
            'email' => 'excluded@example.com',
        ]);

        $results = User::searchNotJoined('Alpha', [$excludedUser->id])->get();

        $this->assertTrue($results->contains('id', $matchedUser->id));
        $this->assertFalse($results->contains('id', $excludedUser->id));
    }

    public function test_search_hits_by_email_only(): void
    {
        // email にのみヒットするユーザー
        $matchedUser = User::factory()->create([
            'name'  => 'NoMatchUser',
            'email' => 'unique-email@example.com',
        ]);

        // 除外ユーザー
        $excludedUser = User::factory()->create([
            'name'  => 'ExcludedUser',
            'email' => 'excluded@example.com',
        ]);

        $results = User::searchNotJoined('unique-email', [$excludedUser->id])->get();

        $this->assertTrue($results->contains('id', $matchedUser->id));
        $this->assertFalse($results->contains('id', $excludedUser->id));
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $user = User::factory()->create([
            'name' => '100% User',
            'email' => 'percent@example.com',
        ]);

        $results = User::searchNotJoined('%', [])->get();

        $this->assertFalse($results->contains('id', $user->id));
    }
}
