<?php
namespace App\Services;

use App\Models\{Order,StatusDefinition,StatusHistory};
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\Schema;

class OrderStatusService
{
    public const FALLBACK_STATUSES=['ordered','arrived_wh','otw_indo','arrived_indo','arrived_gbu','send_to_customer','completed','unclaimed'];
    public const FALLBACK_LABELS=['ordered'=>'Ordered','arrived_wh'=>'Arrived WH','otw_indo'=>'OTW Indo','arrived_indo'=>'Arrived Indo','arrived_gbu'=>'Arrived GBU/KRJASTIP','send_to_customer'=>'Send to Customer','completed'=>'Selesai','unclaimed'=>'Unclaimed'];

    public static function statuses(): array
    {
        if (Schema::hasTable('status_definitions')) {
            $codes=StatusDefinition::activeOrdered()->pluck('code')->all();
            if ($codes) return $codes;
        }
        return self::FALLBACK_STATUSES;
    }

    public static function label(string $status): string
    {
        if (Schema::hasTable('status_definitions')) {
            $label=StatusDefinition::where('code',$status)->value('label');
            if ($label) return $label;
        }
        return self::FALLBACK_LABELS[$status]??ucwords(str_replace('_',' ',$status));
    }

    public static function color(string $status): string
    {
        if (Schema::hasTable('status_definitions')) {
            $color=StatusDefinition::where('code',$status)->value('color');
            if ($color) return $color;
        }
        return '#667085';
    }

    public function update(Order $o,string $status,?int $actor=null,?string $notes=null):void
    {
        if(!in_array($status,self::statuses(),true)) throw new \InvalidArgumentException('Status tidak valid atau sedang dinonaktifkan.');
        $from=$o->status;$o->status=$status;
        if($status==='arrived_gbu'&&!$o->arrived_gbu_at)$o->arrived_gbu_at=now();
        if($status==='completed')$o->completed_at=now();
        $o->save();
        StatusHistory::create(['entity_type'=>'order','entity_id'=>$o->id,'from_status'=>$from,'to_status'=>$status,'changed_by'=>$actor,'changed_at'=>now(),'notes'=>$notes]);
        if($from!==$status&&$o->customer)$o->customer->notify(new OrderStatusChangedNotification($o,$status));
    }
}
