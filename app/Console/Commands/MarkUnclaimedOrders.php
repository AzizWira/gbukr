<?php
namespace App\Console\Commands;
use App\Models\Order;
use App\Services\OrderStatusService;use App\Models\User;use App\Notifications\UnclaimedOwnerNotification;
use Illuminate\Console\Command;
class MarkUnclaimedOrders extends Command{protected $signature='orders:mark-unclaimed';protected $description='Tandai barang unclaimed setelah tiga bulan.';public function handle(OrderStatusService $svc):int{Order::where('status','arrived_gbu')->whereNotNull('arrived_gbu_at')->where('arrived_gbu_at','<=',now()->subMonths(3))->with('customer')->chunkById(100,function($rows)use($svc){foreach($rows as $o){$svc->update($o,'unclaimed',null,'Otomatis setelah 3 bulan sejak Arrived GBU/KRJASTIP');User::where('role','owner')->where('active',true)->get()->each->notify(new UnclaimedOwnerNotification($o));}});return self::SUCCESS;}}
