<?php

namespace Tests\Feature;

use App\Jobs\ProcessLegacyImport;
use App\Models\{GoGroup, ImportRun, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_import_is_queued_and_source_file_is_kept(): void
    {
        Storage::fake('local');
        Queue::fake();

        $owner = User::create([
            'name' => 'Owner Import',
            'email' => 'owner-import@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $go = GoGroup::create(['name' => 'CORTIS', 'status' => 'active']);
        Storage::disk('local')->put('imports/demo.xlsx', 'placeholder');

        $response = $this->actingAs($owner)
            ->withSession([
                'legacy_import_path' => 'imports/demo.xlsx',
                'legacy_import_name' => 'GBU CORTIS.xlsx',
                'legacy_import_preview' => ['estimated_rows' => 1200],
            ])
            ->post(route('owner.import.run'), ['go_group_id' => $go->id]);

        $response->assertRedirect(route('owner.import.index'));
        $run = ImportRun::firstOrFail();
        $this->assertSame('queued', $run->status);
        $this->assertSame($go->id, $run->go_group_id);
        $this->assertSame(1200, $run->total_rows);
        Storage::disk('local')->assertExists('imports/demo.xlsx');
        Queue::assertPushed(ProcessLegacyImport::class, fn ($job) => $job->connection === 'database');
    }

    public function test_same_uploaded_source_cannot_be_queued_twice(): void
    {
        Storage::fake('local');
        Queue::fake();

        $owner = User::create([
            'name' => 'Owner Import',
            'email' => 'owner-import2@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $go = GoGroup::create(['name' => 'SEVENTEEN', 'status' => 'active']);
        Storage::disk('local')->put('imports/same.xlsx', 'placeholder');
        ImportRun::create([
            'user_id' => $owner->id,
            'go_group_id' => $go->id,
            'original_name' => 'same.xlsx',
            'stored_path' => 'imports/same.xlsx',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($owner)
            ->withSession([
                'legacy_import_path' => 'imports/same.xlsx',
                'legacy_import_name' => 'same.xlsx',
                'legacy_import_preview' => ['estimated_rows' => 10],
            ])
            ->post(route('owner.import.run'), ['go_group_id' => $go->id]);

        $response->assertRedirect(route('owner.import.index'));
        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('import_runs', 1);
    }

    public function test_import_run_requires_exactly_one_go_destination(): void
    {
        Storage::fake('local');
        Queue::fake();

        $owner = User::factory()->create([
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        Storage::disk('local')->put('imports/no-go.xlsx', 'placeholder');

        $response = $this->actingAs($owner)
            ->withSession([
                'legacy_import_path' => 'imports/no-go.xlsx',
                'legacy_import_name' => 'no-go.xlsx',
                'legacy_import_preview' => ['estimated_rows' => 10],
            ])
            ->post(route('owner.import.run'), []);

        $response->assertRedirect(route('owner.import.index'));
        $response->assertSessionHasErrors('go_group_id');
        $this->assertDatabaseCount('import_runs', 0);
        Queue::assertNothingPushed();
    }

    public function test_import_run_rejects_existing_and_new_go_at_the_same_time(): void
    {
        Storage::fake('local');
        Queue::fake();

        $owner = User::factory()->create([
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $go = GoGroup::create(['name' => 'Existing GO', 'status' => 'active']);
        Storage::disk('local')->put('imports/double-go.xlsx', 'placeholder');

        $response = $this->actingAs($owner)
            ->withSession([
                'legacy_import_path' => 'imports/double-go.xlsx',
                'legacy_import_name' => 'double-go.xlsx',
                'legacy_import_preview' => ['estimated_rows' => 10],
            ])
            ->post(route('owner.import.run'), [
                'go_group_id' => $go->id,
                'new_go_name' => 'New GO',
            ]);

        $response->assertRedirect(route('owner.import.index'));
        $response->assertSessionHasErrors('go_group_id');
        $this->assertDatabaseCount('import_runs', 0);
    }
}
