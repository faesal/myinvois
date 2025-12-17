<?php

namespace Modules\Category\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Category\Entities\CategoryTranslation;
use Modules\Product\App\Models\Product;

class Category extends Model
{
    use HasFactory;


    protected $appends = ['name'];

    protected $hidden = ['front_translate'];


    public function translate(){
        return $this->belongsTo(CategoryTranslation::class, 'id', 'category_id')->where('lang_code', admin_lang());
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }


    public function front_translate(){
        return $this->belongsTo(CategoryTranslation::class, 'id', 'category_id')->where('lang_code', front_lang());
    }

    public function getNameAttribute()
    {
        return $this->front_translate->name;
    }

}
