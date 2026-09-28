<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'import_run_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('import_run_id');
            });
        }
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'import_run_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('import_run_id');
            });
        }
    }
};
