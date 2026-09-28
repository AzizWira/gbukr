<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'import_snapshot')) {
                $table->json('import_snapshot')->nullable()->after('import_run_id');
            }
            if (!Schema::hasColumn('orders', 'imported_at')) {
                $table->timestamp('imported_at')->nullable()->after('import_snapshot');
            }
            if (!Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (!Schema::hasTable('order_deletion_requests')) {
            Schema::create('order_deletion_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('import_run_id')->nullable()->constrained('import_runs')->nullOnDelete();
                $table->string('source', 30)->default('owner');
                $table->string('status', 30)->default('pending')->index();
                $table->boolean('requires_customer_approval')->default(false);
                $table->text('reason')->nullable();
                $table->json('snapshot')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['customer_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_deletion_requests');

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('orders', 'imported_at')) {
                $table->dropColumn('imported_at');
            }
            if (Schema::hasColumn('orders', 'import_snapshot')) {
                $table->dropColumn('import_snapshot');
            }
        });
    }
};
