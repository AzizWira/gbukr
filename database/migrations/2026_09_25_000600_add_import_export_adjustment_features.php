<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->default('kekurangan')->index();
            $table->string('reason', 80);
            $table->unsignedBigInteger('amount_idr');
            $table->unsignedInteger('estimated_weight_grams')->nullable();
            $table->unsignedInteger('actual_weight_grams')->nullable();
            $table->decimal('original_rate', 18, 4)->nullable();
            $table->decimal('final_rate', 18, 4)->nullable();
            $table->text('notes')->nullable();
            $table->string('legacy_key')->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });


        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedInteger('qty')->nullable()->after('info');
        });

        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('go_group_id')->nullable()->constrained('go_groups')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_path')->unique();
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
        Schema::dropIfExists('import_runs');
        Schema::dropIfExists('order_adjustments');
    }
};
