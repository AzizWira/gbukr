<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLegacyImport;
use App\Models\{GoGroup, ImportRun};
use App\Services\LegacyImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    private const IMPORT_DISK = 'local';
    private const IMPORT_DIR = 'imports';

    public function index()
    {
        return view('owner.import.index', [
            'groups' => GoGroup::orderBy('name')->get(),
            'runs' => ImportRun::with('goGroup')->latest()->limit(12)->get(),
        ]);
    }

    public function preview(Request $request, LegacyImportService $service)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ], [
            'file.required' => 'Pilih file spreadsheet yang akan dipreview.',
            'file.file' => 'File upload tidak valid.',
            'file.max' => 'Ukuran workbook maksimal 20 MB.',
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

            return view('owner.import.index', [
                'preview' => $preview,
                'groups' => GoGroup::orderBy('name')->get(),
                'runs' => ImportRun::with('goGroup')->latest()->limit(12)->get(),
                'importName' => $upload->getClientOriginalName(),
            ]);
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

            $message = 'Workbook gagal dipreview. Pastikan file XLSX/XLS valid dan extension PHP untuk spreadsheet tersedia.';
            if (config('app.debug')) {
                $message .= ' Detail: ' . class_basename($e) . ' — ' . $e->getMessage();
            }

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
            'go_group_id' => ['nullable', 'integer', 'exists:go_groups,id', 'required_without:new_go_name', 'prohibits:new_go_name'],
            'new_go_name' => ['nullable', 'string', 'max:120', 'required_without:go_group_id', 'prohibits:go_group_id'],
        ], [
            'go_group_id.required_without' => 'Pilih GO tujuan atau isi nama GO baru.',
            'new_go_name.required_without' => 'Pilih GO tujuan atau isi nama GO baru.',
            'go_group_id.prohibits' => 'Pilih GO lama atau buat GO baru, jangan keduanya sekaligus.',
            'new_go_name.prohibits' => 'Pilih GO lama atau buat GO baru, jangan keduanya sekaligus.',
        ]);

        if (filled($data['new_go_name'] ?? null)) {
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

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'processed_rows' => $run->processed_rows,
            'total_rows' => $run->total_rows,
            'progress' => $run->progressPercent(),
            'summary' => $run->summary,
            'error_message' => $run->error_message,
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
}
