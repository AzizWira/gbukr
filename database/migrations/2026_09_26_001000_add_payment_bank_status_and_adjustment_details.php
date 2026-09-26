<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('customer_id')->constrained('bank_accounts')->nullOnDelete();
        });

        Schema::table('order_adjustments', function (Blueprint $table) {
            $table->unsignedBigInteger('estimated_shipping_idr')->nullable()->after('actual_weight_grams');
            $table->unsignedBigInteger('actual_shipping_idr')->nullable()->after('estimated_shipping_idr');
        });

        Schema::create('status_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('label', 100);
            $table->string('color', 7)->default('#2d63d7');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('system')->default(false);
            $table->timestamps();
        });

        $defaults = [
            ['ordered','Ordered','#667085',10,1],
            ['arrived_wh','Arrived WH','#7c3aed',20,1],
            ['otw_indo','OTW Indo','#2563eb',30,1],
            ['arrived_indo','Arrived Indo','#0891b2',40,1],
            ['arrived_gbu','Arrived GBU/KRJASTIP','#16a34a',50,1],
            ['send_to_customer','Send to Customer','#f59e0b',60,1],
            ['completed','Selesai','#15803d',70,1],
            ['unclaimed','Unclaimed','#dc2626',80,1],
        ];
        foreach ($defaults as [$code,$label,$color,$sort,$system]) {
            DB::table('status_definitions')->insert([
                'code'=>$code,'label'=>$label,'color'=>$color,'sort_order'=>$sort,'active'=>true,'system'=>(bool)$system,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        }

        foreach ($defaults as [$code,$label]) {
            DB::table('shipments')->where('status',$label)->update(['status'=>$code]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('status_definitions');
        Schema::table('order_adjustments', function (Blueprint $table) {
            $table->dropColumn(['estimated_shipping_idr','actual_shipping_idr']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
        });
    }
};
