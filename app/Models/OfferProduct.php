<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Product\App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OfferProduct extends Model
{
    use HasFactory;

    public function product(){
        return $this->belongsTo(Product::class);
    }
}
