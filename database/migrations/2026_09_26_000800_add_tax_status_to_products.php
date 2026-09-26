<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('tax_status', 30)->default('excluded')->index();
        });

        DB::table('products')
            ->where('ems_tax', true)
            ->update(['tax_status' => 'included_estimate']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tax_status']);
            $table->dropColumn('tax_status');
        });
    }
};
