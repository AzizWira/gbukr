<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Repair migration: aman dijalankan walaupun migration v1.0.22 sebelumnya
        // tercatat tetapi kolom provenance belum benar-benar ada di database.
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'import_run_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('import_run_id')->nullable()->after('batch_id')->constrained('import_runs')->nullOnDelete();
            });
        }

        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'import_run_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('import_run_id')->nullable()->after('order_id')->constrained('import_runs')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Tidak menghapus kolom karena migration ini bersifat repair untuk instalasi v1.0.22.
    }
};
