<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_preview_valid_legacy_workbook(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create([
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'gbukr-import-') . '.xlsx';
        $book = new Spreadsheet();
        $book->getActiveSheet()->setTitle('STATUS BARANG');
        $book->getActiveSheet()->setCellValue('A1', 'Batch');
        $book->createSheet()->setTitle('TAGIHAN KR');
        (new Xlsx($book))->save($tmp);

        $upload = new UploadedFile(
            $tmp,
            'legacy.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($owner)->post(route('owner.import.preview'), [
            'file' => $upload,
        ]);

        $response->assertRedirect(route('owner.import.index'));
        $response->assertSessionHas('legacy_import_path');

        $this->actingAs($owner)
            ->get(route('owner.import.index'))
            ->assertOk()
            ->assertSee('STATUS BARANG')
            ->assertSee('TAGIHAN KR');
    }

    public function test_invalid_workbook_returns_form_error_instead_of_500(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create([
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $upload = UploadedFile::fake()->createWithContent('broken.xlsx', 'not an excel workbook');

        $response = $this->actingAs($owner)
            ->from(route('owner.import.index'))
            ->post(route('owner.import.preview'), ['file' => $upload]);

        $response->assertRedirect(route('owner.import.index'));
        $response->assertSessionHasErrors('file');
    }
}
