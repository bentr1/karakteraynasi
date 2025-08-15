<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'product_id',
        'receipt',
        'credits_added',
    ];

    /**
     * Bu işlemin ait olduğu kullanıcıyı döndürür.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
