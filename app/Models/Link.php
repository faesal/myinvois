<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Link extends Model
{
    use HasFactory;
    protected $table = 'shorten_url'; // Specify the table name
    protected $fillable = [
        'short_code',
        'original_url',
        'expires_at',
        'clicks',
    ];
}