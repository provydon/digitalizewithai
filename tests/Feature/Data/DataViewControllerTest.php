<?php

use App\Models\Data;
use App\Models\SavedDataChart;
use App\Models\SavedDataChat;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('dashboard api data original file returns 404 when no file path', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create([
        'user_id' => $user->id,
        'raw_data' => ['disk' => 'local'],
    ]);

    $this->actingAs($user);
    $response = $this->get(route('dashboard.api.data.original-file', $data));

    $response->assertNotFound();
});

test('dashboard api data original file streams file when path exists', function () {
    $user = User::factory()->create();
    $path = 'digitalize/test-original.txt';
    Storage::disk('local')->put($path, 'file content');
    $data = Data::factory()->create([
        'user_id' => $user->id,
        'raw_data' => ['disk' => 'local', 'path' => $path, 'mime_type' => 'text/plain'],
    ]);

    $this->actingAs($user);
    $response = $this->get(route('dashboard.api.data.original-file', $data));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/plain');
    expect($response->streamedContent())->toBe('file content');
});

test('dashboard api data ask stream requires question', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.ask.stream', $data), []);

    $response->assertStatus(422);
});

test('dashboard api data ask stream returns only final answer for nova', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['Item', 'Color'],
        [['Hat', 'Red'], ['Cape', 'Blue'], ['Mask', 'Red']],
    )->create([
        'user_id' => $user->id,
        'ai_provider' => 'nova',
        'ai_model' => 'nova-2-lite-v1',
    ]);

    $this->actingAs($user);
    $response = $this->post(
        route('dashboard.api.data.ask.stream', $data),
        ['question' => 'What is the most common color?'],
        ['Accept' => 'text/event-stream']
    );

    $response->assertOk();
    expect($response->streamedContent())->toContain('Red')
        ->not->toContain('To determine the most common color')
        ->not->toContain("let's count each color")
        ->not->toContain('Answer:');
});

test('saved chats index returns list for data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);
    SavedDataChat::create([
        'data_id' => $data->id,
        'user_id' => $user->id,
        'name' => 'My Chat',
        'messages' => [['role' => 'user', 'content' => 'Hi']],
    ]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.saved-chats.index', $data));

    $response->assertOk()->assertJsonStructure(['chats']);
    expect($response->json('chats'))->toHaveCount(1);
});

test('saved chat store creates chat', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.saved-chats.store', $data), [
        'name' => 'Test Chat',
        'messages' => [['role' => 'user', 'content' => 'Hello'], ['role' => 'assistant', 'content' => 'Hi']],
    ]);

    $response->assertCreated()->assertJsonStructure(['id', 'name', 'messages']);
    $this->assertDatabaseHas('saved_data_chats', ['data_id' => $data->id, 'user_id' => $user->id]);
});

test('saved chat store requires at least one message', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.saved-chats.store', $data), [
        'messages' => [],
    ]);

    $response->assertStatus(422);
});

test('saved chat update modifies name and messages', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);
    $chat = SavedDataChat::create([
        'data_id' => $data->id,
        'user_id' => $user->id,
        'name' => 'Old',
        'messages' => [['role' => 'user', 'content' => 'Hi']],
    ]);

    $this->actingAs($user);
    $response = $this->patchJson(route('dashboard.api.data.saved-chats.update', [$data, $chat]), [
        'name' => 'Updated Chat',
        'messages' => [['role' => 'user', 'content' => 'Hello'], ['role' => 'assistant', 'content' => 'Hi there']],
    ]);

    $response->assertOk()->assertJsonPath('name', 'Updated Chat');
    $chat->refresh();
    expect($chat->name)->toBe('Updated Chat');
    expect($chat->messages)->toHaveCount(2);
});

test('saved chat destroy deletes own chat', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);
    $chat = SavedDataChat::create([
        'data_id' => $data->id,
        'user_id' => $user->id,
        'name' => 'Chat',
        'messages' => [['role' => 'user', 'content' => 'Hi']],
    ]);

    $this->actingAs($user);
    $response = $this->deleteJson(route('dashboard.api.data.saved-chats.destroy', [$data, $chat]));

    $response->assertOk()->assertJson(['deleted' => true]);
    $this->assertDatabaseMissing('saved_data_chats', ['id' => $chat->id]);
});

test('saved charts index returns list for data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);
    SavedDataChart::create([
        'data_id' => $data->id,
        'user_id' => $user->id,
        'name' => 'My Chart',
        'chart_config' => ['chartType' => 'bar', 'labelColumn' => 0, 'valueColumn' => 1],
    ]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.saved-charts.index', $data));

    $response->assertOk()->assertJsonStructure(['charts']);
    expect($response->json('charts'))->toHaveCount(1);
});

test('saved chart store creates chart', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.saved-charts.store', $data), [
        'name' => 'Sales Chart',
        'chart_config' => ['chartType' => 'bar', 'labelColumn' => 0, 'valueColumn' => 1, 'title' => 'Sales'],
    ]);

    $response->assertCreated()->assertJsonStructure(['id', 'name', 'chart_config']);
    $this->assertDatabaseHas('saved_data_charts', ['data_id' => $data->id, 'user_id' => $user->id]);
});

test('saved chart store requires chart_config', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.saved-charts.store', $data), []);

    $response->assertStatus(422);
});

test('saved chart destroy deletes own chart', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);
    $chart = SavedDataChart::create([
        'data_id' => $data->id,
        'user_id' => $user->id,
        'name' => 'Chart',
        'chart_config' => ['chartType' => 'bar'],
    ]);

    $this->actingAs($user);
    $response = $this->deleteJson(route('dashboard.api.data.saved-charts.destroy', [$data, $chart]));

    $response->assertOk()->assertJson(['deleted' => true]);
    $this->assertDatabaseMissing('saved_data_charts', ['id' => $chart->id]);
});
