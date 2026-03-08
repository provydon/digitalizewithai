<?php

use App\Models\Data;
use App\Models\DataTableRow;
use App\Models\User;

test('dashboard api data rows index requires authentication', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $user->id]);

    $response = $this->getJson(route('dashboard.api.data.rows.index', $data));
    $response->assertStatus(401);
});

test('dashboard api data rows index returns headers and paginated rows for table data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(['Name', 'Value'])->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.rows.index', $data));

    $response->assertOk()
        ->assertJsonPath('headers', ['Name', 'Value'])
        ->assertJsonStructure(['rows', 'meta']);
});

test('dashboard api data rows index returns 404 for doc data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.rows.index', $data));

    $response->assertNotFound();
});

test('dashboard api data rows index returns 404 for other user data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $other->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.rows.index', $data));

    $response->assertNotFound();
});

test('dashboard api data rows store adds row', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(['A', 'B'])->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.rows.store', $data), [
        'cells' => ['x', 'y'],
    ]);

    $response->assertOk()->assertJsonStructure(['row']);
    $this->assertDatabaseHas('data_table_rows', [
        'data_id' => $data->id,
    ]);
    $row = $response->json('row');
    expect($row['cells'])->toBe(['x', 'y']);
});

test('dashboard api data rows store requires cells array', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.rows.store', $data), []);

    $response->assertStatus(422);
});

test('dashboard api data rows update modifies row', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(['A', 'B'])->create(['user_id' => $user->id]);
    $row = $data->tableRows()->create([
        'row_index' => 0,
        'search_content' => 'old',
        'cells' => ['old1', 'old2'],
    ]);

    $this->actingAs($user);
    $response = $this->patchJson(route('dashboard.api.data.rows.update', ['data' => $data->id, 'data_table_row' => $row->id]), [
        'cells' => ['new1', 'new2'],
    ]);

    $response->assertOk()->assertJsonPath('row.cells', ['new1', 'new2']);
    $row->refresh();
    expect($row->cells)->toBe(['new1', 'new2']);
});

test('dashboard api data rows destroy deletes row', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $user->id]);
    $row = $data->tableRows()->create([
        'row_index' => 0,
        'search_content' => 'x',
        'cells' => ['a', 'b'],
    ]);

    $this->actingAs($user);
    $response = $this->deleteJson(route('dashboard.api.data.rows.destroy', ['data' => $data->id, 'data_table_row' => $row->id]));

    $response->assertOk()->assertJson(['deleted' => true]);
    $this->assertDatabaseMissing('data_table_rows', ['id' => $row->id]);
});

test('dashboard api data rows destroy returns 404 for other user data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $other->id]);
    $row = $data->tableRows()->create([
        'row_index' => 0,
        'search_content' => 'x',
        'cells' => ['a', 'b'],
    ]);

    $this->actingAs($user);
    $response = $this->deleteJson(route('dashboard.api.data.rows.destroy', ['data' => $data->id, 'data_table_row' => $row->id]));

    $response->assertNotFound();
});
