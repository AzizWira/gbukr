<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLegacyImport;
use App\Models\{Batch, GoGroup, ImportRun, Order, Shipment};
use App\Services\{LegacyImportService, OrderCleanupService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    private const IMPORT_DISK = 'local';
    private const IMPORT_DIR = 'imports';

    public function index(Request $request)
    {
        $preview = (array) $request->session()->get('legacy_import_preview', []);
        $this->markStaleRunsAsFailed();

        return view('owner.import.index', [
            'preview' => $preview ?: null,
            'importName' => $request->session()->get('legacy_import_name'),
            'groups' => GoGroup::where('status', 'active')->orderBy('name')->get(),
            'runs' => ImportRun::with('goGroup')->latest()->limit(12)->get(),
        ]);
    }

    public function preview(Request $request, LegacyImportService $service)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ], [
            'file.required' => 'Pilih file spreadsheet yang akan dipreview.',
            'file.file' => 'File upload tidak valid.',
            'file.max' => 'Ukuran workbook maksimal 5 MB.',
        ]);

        $upload = $request->file('file');
        $extension = strtolower((string) $upload->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Format file harus XLSX atau XLS.',
            ]);
        }

        $disk = Storage::disk(self::IMPORT_DISK);
        $path = null;

        try {
            if (!$disk->exists(self::IMPORT_DIR)) {
                $disk->makeDirectory(self::IMPORT_DIR);
            }

            $filename = Str::uuid()->toString() . '.' . $extension;
            $path = $upload->storeAs(self::IMPORT_DIR, $filename, self::IMPORT_DISK);

            if (!$path || !$disk->exists($path)) {
                throw new \RuntimeException('File upload tidak berhasil disimpan ke storage lokal.');
            }

            $preview = $service->preview($disk->path($path));

            if (empty($preview['sheets'])) {
                throw new \RuntimeException('Workbook tidak memiliki sheet yang dapat dibaca.');
            }

            if (!$preview['recognized']) {
                throw ValidationException::withMessages([
                    'file' => 'Workbook dapat dibaca, tetapi tidak ditemukan STATUS BARANG atau sheet TAGIHAN yang didukung.',
                ]);
            }

            // File preview lama yang belum pernah dikirim untuk import boleh dibersihkan.
            if ($oldPath = $request->session()->pull('legacy_import_path')) {
                $alreadyQueued = ImportRun::where('stored_path', $oldPath)->exists();
                if (!$alreadyQueued) {
                    $disk->delete($oldPath);
                }
            }

            $request->session()->put('legacy_import_path', $path);
            $request->session()->put('legacy_import_name', $upload->getClientOriginalName());
            $request->session()->put('legacy_import_preview', $preview);

            return redirect()->route('owner.import.index');
        } catch (ValidationException $e) {
            if ($path) {
                $disk->delete($path);
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($path) {
                try {
                    $disk->delete($path);
                } catch (\Throwable) {
                    // Cleanup tidak boleh menutupi error utama.
                }
            }

            Log::error('Legacy import preview failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'original_name' => $upload?->getClientOriginalName(),
                'extension' => $extension,
            ]);

            $message = 'Workbook belum berhasil dibaca. Pastikan file XLSX/XLS valid dan tidak rusak, lalu coba lagi.';

            return back()->withInput()->withErrors(['file' => $message]);
        }
    }

    public function run(Request $request)
    {
        $disk = Storage::disk(self::IMPORT_DISK);
        $path = $request->session()->get('legacy_import_path');
        $originalName = (string) $request->session()->get('legacy_import_name');
        $preview = (array) $request->session()->get('legacy_import_preview', []);

        if (!$path || !$disk->exists($path)) {
            return redirect()->route('owner.import.index')->withErrors([
                'file' => 'File import tidak lagi tersedia. Upload dan preview workbook terlebih dahulu.',
            ]);
        }

        if (ImportRun::where('stored_path', $path)->exists()) {
            $request->session()->forget(['legacy_import_path', 'legacy_import_name', 'legacy_import_preview']);
            return redirect()->route('owner.import.index')->withErrors([
                'file' => 'Workbook ini sudah pernah dimasukkan ke antrean import. Cek riwayat import sebelum mencoba lagi.',
            ]);
        }

        $data = $request->validate([
            'go_group_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('go_groups', 'id')->where(fn ($q) => $q->where('status', 'active'))],
            'new_go_name' => ['nullable', 'string', 'max:120'],
        ]);

        $selectedGo = filled($data['go_group_id'] ?? null);
        $newGo = filled($data['new_go_name'] ?? null);

        if (!$selectedGo && !$newGo) {
            return redirect()->route('owner.import.index')
                ->withInput()
                ->withErrors(['go_group_id' => 'Pilih GO yang sudah ada atau buat GO baru sebelum menjalankan import.']);
        }

        if ($selectedGo && $newGo) {
            return redirect()->route('owner.import.index')
                ->withInput()
                ->withErrors(['go_group_id' => 'Pilih salah satu saja: gunakan GO yang sudah ada atau buat GO baru.']);
        }

        if ($newGo) {
            $name = trim($data['new_go_name']);
            $group = GoGroup::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            if (!$group) {
                $group = GoGroup::create([
                    'name' => $name,
                    'status' => 'active',
                    'notes' => 'Dibuat dari proses migrasi workbook ' . $originalName,
                ]);
            }
        } else {
            $group = GoGroup::findOrFail($data['go_group_id']);
        }

        $run = ImportRun::create([
            'user_id' => $request->user()->id,
            'go_group_id' => $group->id,
            'original_name' => $originalName ?: basename($path),
            'stored_path' => $path,
            'status' => 'queued',
            'total_rows' => (int) ($preview['estimated_rows'] ?? 0),
            'processed_rows' => 0,
        ]);

        ProcessLegacyImport::dispatch($run->id)->onConnection('database');

        $request->session()->forget(['legacy_import_path', 'legacy_import_name', 'legacy_import_preview']);

        return redirect()->route('owner.import.index')->with(
            'success',
            'Import ' . $run->original_name . ' sudah masuk antrean untuk GO ' . $group->name . '. Proses berjalan di background.'
        );
    }

    public function status(Request $request, ImportRun $run)
    {
        abort_unless($request->user()->isOwner(), 403);
        $run = $this->refreshStaleRun($run);

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'status_label' => $run->statusLabel(),
            'processed_rows' => $run->processed_rows,
            'total_rows' => $run->total_rows,
            'progress' => $run->progressPercent(),
            'summary' => $run->summary,
            'error_message' => $run->friendlyErrorMessage(),
            'finished_at' => $run->finished_at?->translatedFormat('d F Y, H.i'),
        ]);
    }

    public function source(Request $request, ImportRun $run)
    {
        abort_unless($request->user()->isOwner(), 403);
        $disk = Storage::disk(self::IMPORT_DISK);
        abort_unless($disk->exists($run->stored_path), 404);

        return response()->download(
            $disk->path($run->stored_path),
            $run->original_name,
            ['Content-Type' => 'application/octet-stream']
        );
    }

    public function retry(Request $request, ImportRun $run)
    {
        abort_unless($request->user()->isOwner(), 403);

        if ($run->status !== 'failed') {
            return back()->withErrors(['import' => 'Hanya import yang gagal yang dapat dicoba ulang.']);
        }

        $disk = Storage::disk(self::IMPORT_DISK);
        if (!$disk->exists($run->stored_path)) {
            return back()->withErrors(['import' => 'File sumber import sudah tidak tersedia. Upload workbook kembali untuk menjalankan import baru.']);
        }

        $run->update([
            'status' => 'queued',
            'processed_rows' => 0,
            'summary' => null,
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);

        ProcessLegacyImport::dispatch($run->id)->onConnection('database');

        return back()->with('success', 'Import ' . $run->original_name . ' dimasukkan kembali ke antrean. Data yang sudah sempat masuk akan diperbarui, bukan digandakan.');
    }

    public function cleanup(Request $request, ImportRun $run, OrderCleanupService $cleanup)
    {
        abort_unless($request->user()->isOwner(), 403);

        if (!in_array($run->status, ['completed', 'failed'], true)) {
            return back()->withErrors(['import' => 'Import yang masih berjalan atau menunggu antrean tidak dapat dibersihkan.']);
        }

        $ordersQuery = Order::with(['invoices.payments', 'batch'])
            ->where('go_group_id', $run->go_group_id);

        $ordersQuery->where(function ($query) use ($run) {
            if (Schema::hasColumn('orders', 'import_run_id')) {
                $query->where('import_run_id', $run->id)
                    ->orWhere(function ($legacy) use ($run) {
                        $legacy->whereNull('import_run_id')
                            ->where('notes', 'like', 'Migrasi ' . $run->original_name . ' |%');
                    });
            } else {
                $query->where('notes', 'like', 'Migrasi ' . $run->original_name . ' |%');
            }
        });

        $orders = $ordersQuery->get();

        if ($orders->isEmpty()) {
            return back()->withErrors(['import' => 'Tidak ditemukan order yang masih terhubung dengan riwayat import ini.']);
        }

        $batchIds = $orders->pluck('batch_id')->filter()->unique()->values();
        $deleted = 0;
        $detached = 0;

        foreach ($orders as $order) {
            if ($cleanup->canDelete($order)) {
                $cleanup->delete($order);
                $deleted++;
            } elseif ($order->batch_id) {
                $cleanup->detachFromBatch($order);
                $detached++;
            }
        }

        $removedBatches = 0;
        foreach (Batch::whereIn('id', $batchIds)->get() as $batch) {
            if (!$batch->orders()->exists() && str_contains($batch->code, '-LEG-')) {
                Shipment::where('source_type', 'batch')->where('source_id', $batch->id)->delete();
                $batch->delete();
                $removedBatches++;
            }
        }

        $summary = (array) $run->summary;
        $summary['cleanup_deleted_orders'] = $deleted;
        $summary['cleanup_detached_orders'] = $detached;
        $summary['cleanup_batches'] = $removedBatches;
        $run->update(['status' => 'rolled_back', 'summary' => $summary]);

        $message = 'Cleanup import selesai. ' . $deleted . ' order tanpa histori finansial dihapus permanen.';
        if ($detached > 0) {
            $message .= ' ' . $detached . ' order berhistori pembayaran dikeluarkan dari Batch, tetapi order/tagihan/pembayaran tetap tersimpan sebagai audit.';
        }
        if ($removedBatches > 0) {
            $message .= ' ' . $removedBatches . ' Batch legacy kosong ikut dihapus.';
        }
        $message .= ' File sumber tetap disimpan.';

        return back()->with('success', $message);
    }

    private function markStaleRunsAsFailed(): void
    {
        ImportRun::where('status', 'running')
            ->where('updated_at', '<', now()->subMinutes(5))
            ->update([
                'status' => 'failed',
                'error_message' => 'Proses import berhenti sebelum selesai. Data yang sudah masuk tetap aman dan import dapat dicoba ulang.',
                'finished_at' => now(),
            ]);
    }

    private function refreshStaleRun(ImportRun $run): ImportRun
    {
        if ($run->status === 'running' && $run->updated_at && $run->updated_at->lt(now()->subMinutes(5))) {
            $run->update([
                'status' => 'failed',
                'error_message' => 'Proses import berhenti sebelum selesai. Data yang sudah masuk tetap aman dan import dapat dicoba ulang.',
                'finished_at' => now(),
            ]);
        }

        return $run->fresh();
    }

}