<?php

use App\Models\Data;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('data index page requires authentication', function () {
    $response = $this->get(route('data.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can visit data index page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('data.index'));
    $response->assertOk();
});

test('dashboard api data index returns paginated list for authenticated user', function () {
    $user = User::factory()->create();
    Data::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.index'));

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta'])
        ->assertJsonPath('meta.total', 3)
        ->assertJsonCount(3, 'data');
});

test('dashboard api data index respects search', function () {
    $user = User::factory()->create();
    Data::factory()->create(['user_id' => $user->id, 'name' => 'Alpha Document']);
    Data::factory()->create(['user_id' => $user->id, 'name' => 'Beta Table']);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.index', ['search' => 'Alpha']));

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('Alpha Document');
});

test('dashboard api data index only returns current user data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Data::factory()->create(['user_id' => $user->id, 'name' => 'Mine']);
    Data::factory()->create(['user_id' => $other->id, 'name' => 'Theirs']);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.index'));

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('Mine');
});

test('dashboard api data destroy deletes own data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->deleteJson(route('dashboard.api.data.destroy', $data));

    $response->assertOk()->assertJson(['deleted' => true]);
    $this->assertDatabaseMissing('data', ['id' => $data->id]);
});

test('dashboard api data destroy returns 404 for other user data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user);
    $response = $this->deleteJson(route('dashboard.api.data.destroy', $data));

    $response->assertNotFound();
    $this->assertDatabaseHas('data', ['id' => $data->id]);
});
