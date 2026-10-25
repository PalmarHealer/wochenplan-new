<?php

use App\Filament\Pages\FAQ;
use App\Help\HelpTopic;
use App\Help\HelpTopics;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    collect(HelpTopics::all())
        ->flatMap(fn (HelpTopic $topic) => $topic->permissions)
        ->push('page_FAQ')
        ->unique()
        ->each(fn ($name) => Permission::findOrCreate($name, 'web'));
});

function visibleTopicIds(User $user): array
{
    test()->actingAs($user);

    return (new FAQ)->groups()->flatten(1)->map(fn ($entry) => $entry['topic']->id)->values()->all();
}

it('has unique ids and a body view for every topic', function () {
    $ids = array_map(fn (HelpTopic $topic) => $topic->id, HelpTopics::all());

    expect($ids)->toBe(array_values(array_unique($ids)));

    foreach (HelpTopics::all() as $topic) {
        expect(view()->exists($topic->view()))->toBeTrue("Missing view for help topic {$topic->id}");
    }
});

it('shows only general topics to a user without permissions', function () {
    $topics = visibleTopicIds(User::factory()->create());

    expect($topics)->toBe(['day-view', 'profile']);
});

it('scopes topics to the permissions of the user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['page_ShiftPlan', 'view_absence']);

    expect(visibleTopicIds($user))->toBe(['day-view', 'profile', 'shift-plan', 'absences']);
});

it('renders every topic for a user with all permissions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::all());
    $this->actingAs($user);

    $groups = (new FAQ)->groups();

    expect($groups->flatten(1))->toHaveCount(count(HelpTopics::all()))
        ->and($groups->keys()->all())->toBe(['Grundlagen', 'Planung', 'Bedienung', 'Verwaltung']);

    $groups->flatten(1)->each(fn ($entry) => expect($entry['body'])->not->toBeEmpty()
        ->and($entry['search'])->toContain(mb_strtolower($entry['topic']->heading)));
});
