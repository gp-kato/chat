<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Group;
use App\Models\User;

class ApplicantCannotTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;
    private ?Group $group = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // すべてのテストで使うユーザーを作成
        $this->group = Group::factory()->create(); // 1回だけグループを作成
    }

    private function applicant(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'role' => 'applicant',
        ]);
    }

    public function test_applicant_cannot_view_chat(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->get(route('groups.messages.show', $this->group->id));

        $response->assertForbidden();
    }

    public function test_applicant_cannot_write_chat(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->post(route('groups.messages.store', $this->group->id), [
            'content' => 'テストメッセージ',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'このグループに参加していません');
    }

    public function test_applicant_cannot_fetch_messages(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->get(route('groups.messages.fetch', $this->group->id));

        $response->assertForbidden();
    }
}
