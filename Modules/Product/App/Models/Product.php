<?php

namespace Modules\Product\App\Models;

use App\Models\Review;
use App\Models\OfferProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Modules\Category\Entities\Category;
use Modules\Order\App\Models\OrderItem;
use App\Models\Scopes\ProductLocationScope;
use Modules\Restaurant\Entities\Restaurant;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Database\factories\ProductFactory;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

    protected $fillable = ['status'];

    protected $appends = ['name','short_description', 'size'];
    protected $hidden = ['product_translate_lang'];

    public function translate_product()
    {
        return $this->belongsTo(ProductTranslation::class,'id','product_id')->where('lang_code','en');
    }

    public function product_translate_lang()
    {
        return $this->belongsTo(ProductTranslation::class,'id','product_id')->where('lang_code', admin_lang());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function offer(): HasOne
    {
        return $this->HasOne(OfferProduct::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getNameAttribute()
    {
        return $this->product_translate_lang->name;
    }
    public function getShortDescriptionAttribute()
    {
        return $this->product_translate_lang->long_description;
    }
    public function getSizeAttribute()
    {
        return $this->product_translate_lang->size;
    }

    public function getSpecificationAttribute()
    {
        return $this->product_translate_lang?->specification;
    }

    protected static function booted()
    {
        static::addGlobalScope(new ProductLocationScope);
    }

}
