<?php

namespace Modules\Page\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactUs extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected static function newFactory()
    {
        return \Modules\Page\Database\factories\ContactUsFactory::new();
    }

    protected $hidden = ['front_translate'];

    protected $appends = ['title'];

    public function translate(){
        return $this->belongsTo(ContactUsTranslation::class, 'id', 'contact_us_id')->where('lang_code' , admin_lang());
    }

    public function front_translate(){
        return $this->belongsTo(ContactUsTranslation::class, 'id', 'contact_us_id')->where('lang_code' , front_lang());
    }

    public function getTitleAttribute()
    {
        return $this->front_translate->title;
    }


}
