<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'analysis_text',
        'detected_features',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'detected_features' => 'array',
    ];

    /**
     * Analizin ait olduğu kullanıcıyı döndürür.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Analize ait fotoğrafları döndürür.
     */
    public function photos()
    {
        return $this->hasMany(Photo::class);
    }
}
