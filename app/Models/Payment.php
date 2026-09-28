<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model
{
    protected $fillable=['customer_id','bank_account_id','payment_number','amount','status','submitted_at','verified_at','verified_by','rejection_reason'];
    protected function casts():array{return ['submitted_at'=>'datetime','verified_at'=>'datetime'];}
    public function customer(){return $this->belongsTo(User::class,'customer_id');}
    public function bankAccount(){return $this->belongsTo(BankAccount::class);}
    public function invoices(){return $this->belongsToMany(Invoice::class, 'invoice_payment')->withPivot('allocated_amount')->withTimestamps();}
    public function proofs(){return $this->hasMany(PaymentProof::class);}
    public function verifier(){return $this->belongsTo(User::class,'verified_by');}
}
