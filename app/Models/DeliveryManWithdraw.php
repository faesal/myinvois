<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryManWithdraw extends Model
{
    use HasFactory;

    protected $table = 'deliveryman_withdraws';

    protected $fillable = [];


    public function deliveryman()
    {
        return $this->belongsTo(DeliveryMan::class, 'deliveryman_id', 'id');
    }

}
