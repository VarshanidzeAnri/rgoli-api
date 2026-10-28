<?php

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    seedDemo();
});

it('uploads several files, detects types and stores them privately under random names', function (): void {
    signIn(demoUser('interior_designer'));
    $off = Project::query()->where('key', 'OFF')->firstOrFail();

    $response = $this->post('/api/v1/files', [
        'project_id' => $off->id,
        'files' => [
            UploadedFile::fake()->image('ფასადი.png', 40, 40),
            UploadedFile::fake()->create('plan.pdf', 120, 'application/pdf'),
        ],
    ], ['Accept' => 'application/json']);

    $response->assertCreated()->assertJsonCount(2, 'data');
    expect(collect($response->json('data'))->pluck('type')->all())->toBe(['image', 'pdf'])
        ->and($response->json('data.0.original_name'))->toBe('ფასადი.png');

    $file = ProjectFile::query()->findOrFail($response->json('data.0.id'));
    expect($file->path)->not->toContain('ფასადი')->and($file->path)->toStartWith('projects/'.$off->id.'/');
    Storage::disk('local')->assertExists($file->path);
});

it('rejects disallowed extensions and oversize files', function (): void {
    signIn(demoUser('owner'));
    $off = Project::query()->where('key', 'OFF')->firstOrFail();
    config(['rgoli.uploads.max_kb' => 100]);

    $this->post('/api/v1/files', [
        'project_id' => $off->id,
        'files' => [UploadedFile::fake()->create('virus.exe', 10), UploadedFile::fake()->create('big.pdf', 500, 'application/pdf')],
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['files.0', 'files.1']);
});

it('cannot upload to an invisible project', function (): void {
    signIn(demoUser('project_manager'));
    $off = Project::query()->where('key', 'OFF')->firstOrFail();

    $this->post('/api/v1/files', ['project_id' => $off->id, 'files' => [UploadedFile::fake()->create('a.pdf', 5, 'application/pdf')]], ['Accept' => 'application/json'])
        ->assertForbidden();
});

it('downloads through an authorized route', function (): void {
    signIn(demoUser('owner'));
    $file = ProjectFile::query()->where('original_name', 'moodboard.png')->firstOrFail();

    $this->get('/api/v1/files/'.$file->id.'/download')->assertOk()->assertDownload('moodboard.png');
    $this->get('/api/v1/files/'.$file->id.'/preview')->assertOk()->assertHeader('Content-Type', 'image/png');
});

it('deletes own files with delete_own and any file with delete_any', function (): void {
    $designer = demoUser('interior_designer');
    $own = ProjectFile::query()->where('uploaded_by', $designer->id)->firstOrFail();
    $others = ProjectFile::query()->where('original_name', 'ფინანსური კალკულატორი.pdf')->firstOrFail();

    signIn($designer);
    $this->getJson('/api/v1/files')->assertOk();
    $this->deleteJson('/api/v1/files/'.$others->id)->assertForbidden();
    $this->deleteJson('/api/v1/files/'.$own->id)->assertOk();

    signIn(demoUser('project_manager'));
    $this->deleteJson('/api/v1/files/'.$others->id)->assertOk();
});

it('filters files by type and project', function (): void {
    signIn(demoUser('owner'));

    $this->getJson('/api/v1/files?type=pdf')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/files?type=bogus')->assertUnprocessable();
});
