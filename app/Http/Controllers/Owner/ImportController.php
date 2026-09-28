<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLegacyImport;
use App\Models\{Batch, GoGroup, ImportRun, Order, OrderDeletionRequest, Shipment};
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

    public function cleanupReview(Request $request, ImportRun $run, OrderCleanupService $cleanup)
    {
        abort_unless($request->user()->isOwner(), 403);
        if (!in_array($run->status, ['completed', 'failed'], true)) {
            return response()->json(['message'=>'Import yang masih berjalan atau sudah selesai dibersihkan tidak dapat direview.'], 422);
        }

        $orders = $this->ordersForRun($run)->with(['customer.customerProfile','items','invoices.payments','batch'])->get();
        $safe=[]; $review=[];
        foreach ($orders as $order) {
            $state=$cleanup->importReview($order);
            $row=$this->cleanupReviewRow($order,$state);
            if ($state['auto_safe']) $safe[]=$row; else $review[]=$row;
        }

        return response()->json([
            'run_id'=>$run->id,
            'file'=>$run->original_name,
            'safe_count'=>count($safe),
            'review_count'=>count($review),
            'already_deleted_count'=>OrderDeletionRequest::where('import_run_id',$run->id)->whereIn('status',['executed','approved'])->count(),
            'safe'=>$safe,
            'review'=>$review,
        ]);
    }

    public function cleanup(Request $request, ImportRun $run, OrderCleanupService $cleanup)
    {
        abort_unless($request->user()->isOwner(), 403);
        if (!in_array($run->status, ['completed', 'failed'], true)) {
            return back()->withErrors(['import'=>'Import yang masih berjalan atau sudah selesai dibersihkan tidak dapat diproses.']);
        }

        $data=$request->validate([
            'review_order_ids'=>['nullable','array','max:500'],
            'review_order_ids.*'=>['integer'],
            'reason'=>['nullable','string','max:500'],
        ]);

        $orders=$this->ordersForRun($run)->with(['customer.customerProfile','items','invoices.payments','batch'])->get();
        if ($orders->isEmpty()) {
            $run->update(['status'=>'rolled_back']);
            return back()->with('success','Tidak ada lagi data aktif dari import ini. Riwayat import ditandai sudah dibersihkan.');
        }

        $safe=collect(); $review=collect();
        foreach($orders as $order){
            $state=$cleanup->importReview($order);
            if($state['auto_safe']) $safe->push($order); else $review->push($order);
        }
        $selectedIds=collect($data['review_order_ids'] ?? [])->map(fn($id)=>(int)$id)->unique();
        $selected=$review->whereIn('id',$selectedIds);
        if($selectedIds->count() !== $selected->count()) {
            throw ValidationException::withMessages(['import'=>'Ada data pilihan yang bukan bagian dari review import ini. Muat ulang popup lalu coba lagi.']);
        }
        if($selected->isNotEmpty() && blank($data['reason'] ?? null)) {
            throw ValidationException::withMessages(['reason'=>'Isi alasan cleanup untuk data yang ditinjau manual. Alasan akan masuk audit dan dapat ditampilkan ke customer bila approval diperlukan.']);
        }
        if($safe->isEmpty() && $selected->isEmpty()) {
            throw ValidationException::withMessages(['import'=>'Tidak ada data aman yang dapat dibersihkan otomatis. Pilih minimal satu data pada daftar review manual.']);
        }

        $batchIds=$safe->concat($selected)->pluck('batch_id')->filter()->unique()->values();
        $deleted=0; $approval=0; $notified=0;
        foreach($safe as $order){
            $result=$cleanup->processDeletion($order,$request->user(),'Cleanup otomatis hasil import '.$run->original_name,'import_cleanup',$run);
            if($result['status']==='deleted') $deleted++;
        }
        foreach($selected as $order){
            $result=$cleanup->processDeletion($order,$request->user(),$data['reason'] ?? null,'import_cleanup',$run);
            if($result['status']==='pending_approval') $approval++;
            else { $deleted++; if($result['policy']['linked']) $notified++; }
        }

        $removedBatches=0;
        foreach(Batch::whereIn('id',$batchIds)->get() as $batch){
            if(!$batch->orders()->exists() && str_contains($batch->code,'-LEG-')){
                Shipment::where('source_type','batch')->where('source_id',$batch->id)->delete();
                $batch->delete(); $removedBatches++;
            }
        }

        $remaining=$this->ordersForRun($run)->count();
        $summary=(array)$run->summary;
        $summary['cleanup_deleted_orders']=($summary['cleanup_deleted_orders'] ?? 0)+$deleted;
        $summary['cleanup_pending_approval']=$approval;
        $summary['cleanup_notified_customers']=($summary['cleanup_notified_customers'] ?? 0)+$notified;
        $summary['cleanup_batches']=($summary['cleanup_batches'] ?? 0)+$removedBatches;
        $summary['cleanup_remaining_orders']=$remaining;
        $run->update(['status'=>$remaining===0?'rolled_back':$run->status,'summary'=>$summary]);

        $message=$deleted.' order berhasil dibersihkan.';
        if($approval>0) $message.=' '.$approval.' order menunggu persetujuan customer dan belum dihapus.';
        if($removedBatches>0) $message.=' '.$removedBatches.' Batch legacy kosong ikut dihapus.';
        if($remaining>0) $message.=' Masih ada '.$remaining.' order yang perlu ditinjau/menunggu approval.';
        return back()->with('success',$message);
    }

    private function ordersForRun(ImportRun $run)
    {
        $query=Order::where('go_group_id',$run->go_group_id);
        return $query->where(function($query) use($run){
            if(Schema::hasColumn('orders','import_run_id')){
                $query->where('import_run_id',$run->id)
                    ->orWhere(function($legacy) use($run){
                        $legacy->whereNull('import_run_id')->where('notes','like','Migrasi '.$run->original_name.' |%');
                    });
            } else {
                $query->where('notes','like','Migrasi '.$run->original_name.' |%');
            }
        });
    }

    private function cleanupReviewRow(Order $order, array $state): array
    {
        $p=$state['policy'];
        $reasons=$state['changes'];
        if($state['new_activity']) $reasons[]='Terdapat aktivitas baru setelah import.';
        if($p['linked']) $reasons[]='Data sudah terhubung ke akun customer.';
        if($p['payment_state']!=='none') $reasons[]=$p['payment_label'].'.';
        return [
            'id'=>$order->id,
            'order_number'=>$order->order_number,
            'customer'=>$order->customer?->name ?: '-',
            'account_status'=>$p['account_label'],
            'linked'=>$p['linked'],
            'payment_status'=>$p['payment_label'],
            'payment_state'=>$p['payment_state'],
            'action'=>$p['action'],
            'action_label'=>$p['action_label'],
            'batch'=>$order->batch?->code ?: '-',
            'items'=>$order->items->pluck('item_name')->take(3)->join(', ') ?: '-',
            'changed'=>!empty($state['changes']),
            'new_activity'=>$state['new_activity'],
            'reasons'=>array_values(array_unique($reasons)),
        ];
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