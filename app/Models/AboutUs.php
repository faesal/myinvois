<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AboutUs extends Model
{
    use HasFactory;

    protected $appends = ['description', 'title', 'customer_title', 'customer_des', 'branch_title', 'branch_des'];

    protected $hidden = ['front_translate'];

    public function translate(): BelongsTo
    {
        return $this->belongsTo(AboutUsTranslation::class, 'id', 'about_us_id')->where('lang_code', admin_lang());
    }

    public function front_translate(): BelongsTo
    {
        return $this->belongsTo(AboutUsTranslation::class, 'id', 'about_us_id')->where('lang_code', front_lang());
    }

    public function getDescriptionAttribute()
    {
        return $this->front_translate?->description;
    }

    public function getTitleAttribute()
    {
        return $this->front_translate?->title;
    }

    public function getCustomerTitleAttribute()
    {
        return $this->front_translate?->customer_title;
    }
    public function getCustomerDesAttribute()
    {
        return $this->front_translate?->customer_des;
    }
    public function getBranchDesAttribute()
    {
        return $this->front_translate?->branch_des;
    }
    public function getBranchTitleAttribute()
    {
        return $this->front_translate?->branch_title;
    }
}
