<?php

namespace App\Jobs;

use App\Models\ImportRun;
use App\Services\LegacyImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessLegacyImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900;
    public bool $failOnTimeout = true;

    public function __construct(public int $importRunId)
    {
    }

    public function handle(LegacyImportService $service): void
    {
        $run = ImportRun::find($this->importRunId);
        if (!$run || $run->status === 'completed') {
            return;
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($run->stored_path)) {
            $run->update([
                'status' => 'failed',
                'error_message' => 'File sumber import tidak ditemukan. Upload workbook kembali untuk membuat proses import baru.',
                'finished_at' => now(),
            ]);
            return;
        }

        $run->update([
            'status' => 'running',
            'started_at' => $run->started_at ?: now(),
            'error_message' => null,
        ]);

        try {
            $summary = $service->import(
                $disk->path($run->stored_path),
                (int) $run->go_group_id,
                $run->original_name,
                function (int $processed, int $total) use ($run): void {
                    $run->forceFill([
                        'processed_rows' => $processed,
                        'total_rows' => $total,
                    ])->saveQuietly();
                },
                $run->id
            );

            $run->update([
                'status' => 'completed',
                'processed_rows' => max($run->processed_rows, $run->total_rows),
                'summary' => $summary,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $this->markFailed($e);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception ?: new \RuntimeException('Worker menghentikan proses import.'));
    }

    private function markFailed(Throwable $e): void
    {
        Log::error('Legacy import background job failed', [
            'import_run_id' => $this->importRunId,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);

        $run = ImportRun::find($this->importRunId);
        if (!$run || $run->status === 'completed') {
            return;
        }

        $run->update([
            'status' => 'failed',
            'error_message' => $this->friendlyMessage($e),
            'finished_at' => now(),
        ]);
    }

    private function friendlyMessage(Throwable $e): string
    {
        $message = $e->getMessage();

        if (stripos($message, 'Allowed memory size') !== false) {
            return 'Workbook terlalu besar untuk diproses sekaligus. Proses dihentikan dengan aman; file asli tetap tersimpan dan dapat dicoba ulang.';
        }

        if ($e instanceof QueryException && stripos($message, 'import_run_id') !== false) {
            return 'Struktur database belum mengikuti pembaruan import terbaru. Jalankan pembaruan database, lalu coba import lagi.';
        }

        if ($e instanceof QueryException) {
            return 'Sebagian data tidak dapat disimpan ke database. File asli tetap tersimpan dan import dapat dicoba ulang setelah data diperiksa.';
        }

        return 'Import belum berhasil diselesaikan. File asli tetap tersimpan dan proses dapat dicoba ulang tanpa menggandakan data yang sudah sempat masuk.';
    }
}
