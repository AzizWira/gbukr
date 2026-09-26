<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('admin_enabled')->default(false)->after('role')->index();
        });

        // Model lama memakai role=admin sebagai akun staff terpisah.
        // Mulai versi ini Admin adalah kemampuan tambahan pada akun customer biasa.
        DB::table('users')->where('role', 'admin')->update([
            'role' => 'customer',
            'admin_enabled' => true,
        ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'customer')
            ->where('admin_enabled', true)
            ->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_enabled');
        });
    }
};
