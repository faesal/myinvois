<?php

namespace Modules\Blog\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Blog\Database\factories\BlogFactory;

class Blog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected $hidden = ['front_translate'];

    protected $appends = ['title', 'description', 'seo_title', 'seo_description'];

    protected static function newFactory(): BlogFactory
    {

    }

    public function category(){
        return $this->belongsTo(BlogCategory::class, 'blog_category_id', 'id');
    }

    public function translate(){
        return $this->belongsTo(BlogTranslation::class, 'id', 'blog_id')->where('lang_code', admin_lang());
    }

    public function front_translate(){
        return $this->belongsTo(BlogTranslation::class, 'id', 'blog_id')->where('lang_code', front_lang());
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function getTitleAttribute(){
        return $this->front_translate?->title;
    }

    public function getDescriptionAttribute(){
        return $this->front_translate?->description;
    }

    public function getSeoTitleAttribute(){
        return $this->front_translate?->seo_title;
    }

    public function getSeoDescriptionAttribute(){
        return $this->front_translate?->seo_description;
    }






}
