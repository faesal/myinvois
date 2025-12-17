<?php

namespace Modules\Coupon\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Coupon\Database\factories\CouponFactory;

class Coupon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

    const STATUS_ACTIVE = 'enable';

    const STATUS_INACTIVE = 'disable';

    const DISCOUNT_PERCENTAGE = 'percentage';

    const DISCOUNT_AMOUNT = 'amount';

    protected $fillable = [];

}
