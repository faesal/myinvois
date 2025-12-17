<?php

namespace Modules\Addon\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Addon\Database\factories\AddonFactory;
use Modules\Restaurant\Entities\Restaurant;

class Addon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

    protected $appends = ['name'];

    protected $hidden = ['front_translate'];

    public function translate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AddonTranslation::class, 'id', 'addon_id')->where('lang_code', admin_lang());
    }

    public function front_translate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AddonTranslation::class, 'id', 'addon_id')->where('lang_code', front_lang());
    }

    public function restaurant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function getNameAttribute()
    {
        return $this->front_translate?->name;
    }


}
