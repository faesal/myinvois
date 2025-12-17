<?php

namespace Modules\Cuisine\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Cuisine\Entities\CuisineTranslation;

class Cuisine extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'enable';

    const STATUS_INACTIVE = 'disable';

    protected $fillable = [];

    protected $appends = ['name'];

    protected $hidden = ['front_translate'];

    public function front_translate(){
        return $this->belongsTo(CuisineTranslation::class, 'id', 'cuisine_id')->where('lang_code', front_lang());
    }

    public function getNameAttribute()
    {
        return $this->front_translate->name;
    }

    public function translate(){
        return $this->belongsTo(CuisineTranslation::class, 'id', 'cuisine_id')->where('lang_code', admin_lang());
    }


}
