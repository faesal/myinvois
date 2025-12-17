<?php

namespace Modules\City\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\City\Entities\CityTranslation;
use Modules\Listing\Entities\Listing;


class City extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $appends = ['name'];

    protected $hidden = ['front_translate'];

    protected static function newFactory()
    {
        return \Modules\City\Database\factories\CityFactory::new();
    }

    public function translate(){
        return $this->belongsTo(CityTranslation::class, 'id', 'city_id')->where('lang_code', admin_lang());
    }

    public function front_translate(){
        return $this->belongsTo(CityTranslation::class, 'id', 'city_id')->where('lang_code', front_lang());
    }

    public function getNameAttribute()
    {
        return $this->front_translate->name;
    }






}
