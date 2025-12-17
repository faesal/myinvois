<?php

namespace Modules\Restaurant\Entities;

use App\Models\Review;
use Modules\City\Entities\City;
use App\Models\RestaurantWishlist;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\App\Models\Product;
use App\Models\Scopes\RestaurantLocationScope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Restaurant extends Authenticatable
{
    use HasFactory;

    protected $fillable = [];

    public function city(){
        return $this->belongsTo(City::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(RestaurantWishlist::class);
    }

    protected static function booted()
    {
        static::addGlobalScope(new RestaurantLocationScope);
    }
    
}
