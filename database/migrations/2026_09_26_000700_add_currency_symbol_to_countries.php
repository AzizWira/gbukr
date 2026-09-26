<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('countries', 'currency_symbol')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->string('currency_symbol', 12)->nullable()->after('currency_code');
            });
        }

        $symbols = [
            'KRW' => '₩',
            'JPY' => '¥',
            'CNY' => '¥',
            'USD' => '$',
            'PHP' => '₱',
            'THB' => '฿',
            'IDR' => 'Rp',
            'MYR' => 'RM',
            'TWD' => 'NT$',
            'SGD' => 'S$',
        ];

        foreach ($symbols as $code => $symbol) {
            DB::table('countries')
                ->where('currency_code', $code)
                ->where(function ($query) {
                    $query->whereNull('currency_symbol')->orWhere('currency_symbol', '');
                })
                ->update(['currency_symbol' => $symbol]);
        }

        DB::table('countries')
            ->where(function ($query) {
                $query->whereNull('currency_symbol')->orWhere('currency_symbol', '');
            })
            ->update(['currency_symbol' => DB::raw('currency_code')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('countries', 'currency_symbol')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('currency_symbol');
            });
        }
    }
};
