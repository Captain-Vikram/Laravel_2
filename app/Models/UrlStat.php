<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrlStat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url_id',
        'ip_address',
        'user_agent',
        'referrer',
        'country',
        'device_type'
    ];

    /**
     * Get the URL that owns this stat.
     */
    public function url()
    {
        return $this->belongsTo(Url::class);
    }
}
