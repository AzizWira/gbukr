<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use App\Models\OrderDeletionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderDeletionApprovalRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(public OrderDeletionRequest $deletionRequest) {}
    public function via(object $notifiable): array { return ['mail','database']; }
    public function toMail(object $notifiable): BrandedNotificationMail
    {
        $s=(array)$this->deletionRequest->snapshot;
        return (new BrandedNotificationMail(
            subjectLine:'Persetujuan penghapusan order — GBUKPOP x KRJASTIP',
            recipientName:$notifiable->name,
            headline:'Persetujuan penghapusan order',
            lines:[
                'Owner mengajukan penghapusan order '.($s['order_number'] ?? '-').'.',
                'Alasan: '.($this->deletionRequest->reason ?: '-'),
                'Periksa rincian tagihan dan pembayaran sebelum menyetujui atau menolak.',
            ],
            actionText:'Tinjau permintaan',
            actionUrl:route('customer.deletion-requests.show',$this->deletionRequest),
        ))->to($notifiable->email,$notifiable->name);
    }
    public function toArray(object $notifiable): array
    {
        return ['title'=>'Persetujuan penghapusan order','deletion_request_id'=>$this->deletionRequest->id,'order_number'=>$this->deletionRequest->snapshot['order_number'] ?? null,'status'=>'pending'];
    }
}
