<?php

namespace Tests\Feature\Service;

use App\Events\MessageEvent;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;

    private ?Group $group = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // 1回だけユーザーを作成
        $this->group = Group::factory()->create(); // 1回だけグループを作成
    }

    public function test_getRecentMessages(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $message = Message::factory()
            ->for($this->user)
            ->for($this->group)
            ->create();

        $messages = $service->getRecentMessages($this->group, 1);

        $this->assertCount(1, $messages);
        $this->assertTrue($messages->first()->relationLoaded('user'));
        $this->assertSame($this->user->id, $messages->first()->user->id);
    }

    public function test_can_get_latest_50_just(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $created = Message::factory()->count(50)->create([
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);

        $messages = $service->getRecentMessages($this->group, 50);

        $this->assertCount(50, $messages);
        $this->assertTrue(
            $messages->pluck('id')->contains($created->last()->id)
        );
    }

    public function test_getRecentMessages_returns_messages_in_id_ascending_order(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        Message::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);

        $messages = $service->getRecentMessages($this->group, 3);

        $this->assertCount(3, $messages);
        $this->assertSame(
            $messages->pluck('id')->sort()->values()->toArray(),
            $messages->pluck('id')->toArray()
        );
    }

    public function test_can_get_is_limited_to_the_latest_50(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $created = Message::factory()->count(51)->create([
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);

        $messages = $service->getRecentMessages($this->group, 50);

        $this->assertCount(50, $messages);
        $this->assertFalse(
            $messages->pluck('id')->contains($created->first()->id)
        );
        $this->assertTrue(
            $messages->pluck('id')->contains($created->last()->id)
        );
    }

    public function test_can_post_message(): void
    {
        Event::fake();

        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $request = new Request([
            'content' => 'content',
        ]);

        $message = $service->post($this->group, $request);

        $this->assertDatabaseHas('messages', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'content' => 'content',
        ]);

        Event::assertDispatched(MessageEvent::class, function (MessageEvent $event) use ($message) {
            return $event->message->is($message)
                && $event->message->group_id === $this->group->id
                && $event->message->user_id === $this->user->id
                && $event->message->content === 'content';
        });
    }

    public function test_post_throws_validation_exception_when_content_exceeds_max_length(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $content = str_repeat('a', 141);

        $this->expectException(ValidationException::class);

        try {
            $service->post($this->group, new Request([
                'content' => $content,
            ]));
        } finally {
            $this->assertDatabaseMissing('messages', [
                'group_id' => $this->group->id,
                'user_id' => $this->user->id,
                'content' => $content,
            ]);
        }
    }

    public function test_fetch_returns_empty_html_when_0_messages(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $messages = $service->fetch($this->group, null);

        $this->assertFalse($messages['has_more']);
        $this->assertEmpty($messages['html']);
    }

    public function test_fetch_returns_50_messages_and_has_more_false_when_50_messges(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        Message::factory()->count(50)->create([
            'group_id' => $this->group->id,
        ]);

        $messages = $service->fetch($this->group, null);

        preg_match_all('/data-id="([^"]+)"/', $messages['html'], $matches);

        $this->assertFalse($messages['has_more']);
        $this->assertIsString($messages['html']);
        $this->assertCount(50, $matches[1]);
    }

    public function test_fetch_returns_50_messages_and_has_more_true_when_51_messges(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        Message::factory()
            ->for($this->user)
            ->for($this->group)
            ->count(51)
            ->create();

        $messages = $service->fetch($this->group, null);

        preg_match_all('/data-id="([^"]+)"/', $messages['html'], $matches);

        $this->assertTrue($messages['has_more']);
        $this->assertIsString($messages['html']);
        $this->assertCount(50, $matches[1]);
    }

    public function test_fetch_with_before_id_returns_only_messages_before_that_id(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $created = Message::factory()->count(5)->create([
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);

        $beforeId = $created->get(2)->id;

        $result = $service->fetch($this->group, $beforeId);

        $html = $result['html'];

        $this->assertStringContainsString('data-id="'.$created->first()->id.'"', $html);
        $this->assertStringContainsString('data-id="'.$created->get(1)->id.'"', $html);

        $this->assertStringNotContainsString('data-id="'.$created->get(2)->id.'"', $html);
        $this->assertStringNotContainsString('data-id="'.$created->last()->id.'"', $html);

        $this->assertFalse($result['has_more']);
    }

    public function test_fetch_results_are_sorted_in_ascending_order_by_id(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $messages = Message::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);

        $result = $service->fetch($this->group, null);

        preg_match_all('/data-id="(\d+)"/', $result['html'], $matches);

        $this->assertSame(
            $messages->pluck('id')->sort()->values()->toArray(),
            array_map('intval', $matches[1])
        );
    }

    public function test_fetch_should_not_include_messages_from_other_groups(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $otherGroup = Group::factory()->create();

        $service = app(MessageService::class);

        $message = Message::factory()->create([
            'group_id' => $this->group->id,
        ]);

        $otherMessage = Message::factory()->create([
            'group_id' => $otherGroup->id,
        ]);

        $messages = $service->fetch($this->group, null);

        $this->assertStringContainsString( $message->content, $messages['html'] );
        $this->assertStringNotContainsString( $otherMessage->content, $messages['html'] );
    }


    public function test_fetch_only_target_message_is_included_in_generated_html(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $messages = Message::factory()->count(51)->sequence(fn ($sequence) => [
            'content' => "message-{$sequence->index}",
                ])->create([
            'group_id' => $this->group->id,
        ]);

        $result = $service->fetch($this->group, null);

        $this->assertStringContainsString(
            $messages->last()->content,
            $result['html'],
        );
        $this->assertStringNotContainsString(
            $messages->first()->content,
            $result['html'],
        );
    }

    public function test_fetch_generated_html_does_not_include_messages_from_other_groups(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $otherGroup = Group::factory()->create();

        $service = app(MessageService::class);

        $messages = Message::factory()->count(51)->create([
            'group_id' => $this->group->id,
        ]);

        $otherMessages = Message::factory()->count(51)->create([
            'group_id' => $otherGroup->id,
        ]);

        $result = $service->fetch($this->group, null);

        $this->assertTrue($result['has_more']);
        $this->assertIsString($result['html']);

        $this->assertStringContainsString(
            $messages->last()->content,
            $result['html'],
        );
        $this->assertStringNotContainsString(
            $otherMessages->last()->content,
            $result['html'],
        );
    }
}
