<?php
namespace App\Console\Commands;
use App\Models\Invoice;use App\Notifications\InvoiceDeadlineNotification;use Illuminate\Console\Command;
class RefreshInvoicePenalties extends Command{protected $signature='invoices:refresh-penalties';protected $description='Perbarui denda DP dan status deadline.';public function handle():int{Invoice::whereNotNull('deadline_at')->whereIn('status',['unpaid','partial','overdue'])->with('customer')->chunkById(200,function($rows){foreach($rows as $i){if($i->type==='dp')$i->recalculatePenalty();if($i->deadline_at?->isPast()&&$i->status!=='paid'){$i->status='overdue';if(!$i->overdue_notified_at){$i->overdue_notified_at=now();$i->save();$i->customer?->notify(new InvoiceDeadlineNotification($i));}else{$i->save();}}}});return self::SUCCESS;}}
