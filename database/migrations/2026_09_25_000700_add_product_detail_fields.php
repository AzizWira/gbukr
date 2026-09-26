<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('item_fee_idr')->default(0);
            $table->boolean('free_shipping')->default(false);
            $table->boolean('ems_tax')->default(false);
            $table->boolean('apply_fansign')->default(false);
            $table->string('location_note', 160)->nullable();
            $table->date('event_date')->nullable();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('source_label', 120)->nullable();
            $table->text('details')->nullable();
            $table->unsignedInteger('estimated_weight_grams')->nullable();
            $table->unsignedBigInteger('dp_amount_idr')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['source_label', 'details', 'estimated_weight_grams', 'dp_amount_idr']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['item_fee_idr', 'free_shipping', 'ems_tax', 'apply_fansign', 'location_note', 'event_date']);
        });
    }
};
