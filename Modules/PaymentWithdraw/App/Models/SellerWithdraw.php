<?php

namespace Modules\PaymentWithdraw\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PaymentWithdraw\Database\factories\SellerWithdrawFactory;
use Modules\Restaurant\Entities\Restaurant;

class SellerWithdraw extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'seller_id');
    }
}
