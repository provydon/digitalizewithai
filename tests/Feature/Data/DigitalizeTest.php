<?php

use App\Models\Data;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

test('digitalize options requires authentication', function () {
    $response = $this->getJson(route('dashboard.api.digitalize-options'));
    $response->assertStatus(401);
});

test('digitalize options returns providers, default, and max_file_size_bytes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson(route('dashboard.api.digitalize-options'));

    $response->assertOk()
        ->assertJsonStructure(['providers', 'default_provider', 'max_file_size_bytes'])
        ->assertJsonPath('max_file_size_bytes', config('upload.max_file_size_mb', 100) * 1024 * 1024);
});

test('digitalize options max_file_size_bytes reflects config', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Config::set('upload.max_file_size_mb', 100);

    $response = $this->getJson(route('dashboard.api.digitalize-options'));

    $response->assertOk()
        ->assertJsonPath('max_file_size_bytes', 100 * 1024 * 1024);
});

test('digitalize store requires authentication', function () {
    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->postJson(route('dashboard.digitalize'), [
        'file' => $file,
    ]);

    $response->assertStatus(401);
});

test('digitalize store accepts image and creates data record', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $this->actingAs($user);
    $response = $this->post(route('dashboard.digitalize'), [
        'file' => $file,
    ]);

    $response->assertStatus(202);
    $response->assertJsonStructure(['id', 'name', 'status', 'digital_data']);
    $response->assertJsonPath('status', 'processing');

    $id = $response->json('id');
    $this->assertDatabaseHas('data', [
        'id' => $id,
        'user_id' => $user->id,
        'status' => 'processing',
    ]);
});

test('digitalize store stores file on disk via streaming', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $this->actingAs($user);
    $response = $this->post(route('dashboard.digitalize'), [
        'file' => $file,
    ]);

    $response->assertStatus(202);
    $data = Data::find($response->json('id'));
    expect($data)->not->toBeNull();
    $raw = $data->raw_data;
    expect($raw)->toHaveKey('disk');
    expect($raw)->toHaveKey('path');
    expect(Storage::disk($raw['disk'])->exists($raw['path']))->toBeTrue();
    expect(Storage::disk($raw['disk'])->size($raw['path']))->toBeGreaterThan(0);
});

test('digitalize store rejects invalid mime type', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $this->actingAs($user);
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('dashboard.digitalize'), [
            'file' => $file,
        ]);

    $response->assertStatus(422);
});

test('digitalize store batch requires at least two files', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('one.jpg', 100, 100);

    $this->actingAs($user);
    $response = $this->post(route('dashboard.digitalize.batch'), [
        'files' => [$file],
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'Use storeBatch only when uploading 2 or more files. Use the single-file upload for one file.']);
});

test('digitalize store batch accepts multiple images and creates one data record', function () {
    $user = User::factory()->create();
    $files = [
        UploadedFile::fake()->image('a.jpg', 100, 100),
        UploadedFile::fake()->image('b.jpg', 100, 100),
    ];

    $this->actingAs($user);
    $response = $this->post(route('dashboard.digitalize.batch'), [
        'files' => $files,
    ]);

    $response->assertStatus(202);
    $response->assertJsonStructure(['id', 'name', 'status', 'digital_data']);
    $id = $response->json('id');
    $this->assertDatabaseHas('data', [
        'id' => $id,
        'user_id' => $user->id,
        'status' => 'processing',
    ]);
});

test('digitalize store batch stores all files on disk', function () {
    $user = User::factory()->create();
    $files = [
        UploadedFile::fake()->image('a.jpg', 100, 100),
        UploadedFile::fake()->image('b.jpg', 100, 100),
    ];

    $this->actingAs($user);
    $response = $this->post(route('dashboard.digitalize.batch'), [
        'files' => $files,
    ]);

    $response->assertStatus(202);
    $data = Data::find($response->json('id'));
    expect($data)->not->toBeNull();
    $raw = $data->raw_data;
    expect($raw)->toHaveKey('files');
    $filesMeta = $raw['files'];
    expect($filesMeta)->toHaveCount(2);
    foreach ($filesMeta as $entry) {
        expect(Storage::disk($entry['disk'])->exists($entry['path']))->toBeTrue();
        expect(Storage::disk($entry['disk'])->size($entry['path']))->toBeGreaterThan(0);
    }
});

test('digitalize store rejects file over configured max size', function () {
    $user = User::factory()->create();
    Config::set('upload.max_file_size_mb', 20);
    $maxKb = 20 * 1024;
    $file = UploadedFile::fake()->create('large.jpg', $maxKb + 1024, 'image/jpeg');

    $this->actingAs($user);
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('dashboard.digitalize'), [
            'file' => $file,
        ]);

    $response->assertStatus(422);
});

test('digitalize store batch rejects file over configured max size', function () {
    $user = User::factory()->create();
    Config::set('upload.max_file_size_mb', 20);
    $maxKb = 20 * 1024;
    $files = [
        UploadedFile::fake()->image('a.jpg', 100, 100),
        UploadedFile::fake()->create('large.jpg', $maxKb + 1024, 'image/jpeg'),
    ];

    $this->actingAs($user);
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('dashboard.digitalize.batch'), [
            'files' => $files,
        ]);

    $response->assertStatus(422);
});

test('append to table returns 422 for doc data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $this->actingAs($user);
    $response = $this->post(route('dashboard.api.data.append-rows', $data), [
        'file' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'This record is not a table. Appending is only supported for tables.']);
});

test('append to doc returns 422 for table data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $user->id]);
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $this->actingAs($user);
    $response = $this->post(route('dashboard.api.data.append-doc', $data), [
        'file' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'This record is not a document. Appending is only supported for documents.']);
});

test('append to table returns 404 for other user data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $other->id]);
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $this->actingAs($user);
    $response = $this->post(route('dashboard.api.data.append-rows', $data), [
        'file' => $file,
    ]);

    $response->assertNotFound();
});
