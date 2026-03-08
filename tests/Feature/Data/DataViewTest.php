<?php

use App\Models\Data;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('data show page requires authentication', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('dashboard.data.show', $data));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view own data show page', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->get(route('dashboard.data.show', $data));

    $response->assertOk();
});

test('data show page returns 404 for other user data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user);
    $response = $this->get(route('dashboard.data.show', $data));

    $response->assertNotFound();
});

test('dashboard api data show returns json for own data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.show', $data));

    $response->assertOk()
        ->assertJsonPath('id', $data->id)
        ->assertJsonPath('name', $data->name)
        ->assertJsonStructure(['digital_data', 'raw_data']);
});

test('dashboard api data show returns 404 for other user data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.show', $data));

    $response->assertNotFound();
});

test('dashboard api data update name', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

    $this->actingAs($user);
    $response = $this->patchJson(route('dashboard.api.data.update', $data), [
        'name' => 'New Name',
    ]);

    $response->assertOk()->assertJsonPath('name', 'New Name');
    $data->refresh();
    expect($data->name)->toBe('New Name');
});

test('dashboard api data update name rejects empty', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->patchJson(route('dashboard.api.data.update', $data), [
        'name' => '   ',
    ]);

    $response->assertStatus(422);
});

test('dashboard api doc page returns page content for doc data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(2)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.doc-page', ['data' => $data->id, 'page' => 1]));

    $response->assertOk()
        ->assertJsonStructure(['page', 'total_pages', 'content'])
        ->assertJsonPath('page', 1)
        ->assertJsonPath('total_pages', 2);
});

test('dashboard api doc content returns full content for doc data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.doc-content', $data));

    $response->assertOk()->assertJsonStructure(['content']);
});

test('dashboard api doc content returns 404 for table data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.doc-content', $data));

    $response->assertNotFound();
});

test('dashboard api update doc content updates doc', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->patchJson(route('dashboard.api.data.doc-content.update', $data), [
        'content' => 'Updated paragraph text.',
    ]);

    $response->assertOk()->assertJsonPath('content', 'Updated paragraph text.');
    $data->refresh();
    expect($data->digital_data['content'])->toContain('Updated paragraph text.');
});

test('dashboard api ask requires question', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.ask', $data), []);

    $response->assertStatus(422);
});

test('dashboard api chart suggestion requires table data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data));

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'Table data is required for chart suggestion.']);
});
