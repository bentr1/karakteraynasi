<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'analysis_id',
        'photo_url',
        'profile_type',
    ];

    /**
     * Fotoğrafın ait olduğu analizi döndürür.
     */
    public function analysis()
    {
        return $this->belongsTo(Analysis::class);
    }
}
