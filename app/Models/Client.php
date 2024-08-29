<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory;

    public mixed $user_id;
    protected $fillable = [
        'surname',
        'adresse',
        'telephone',

    ];

    function user() {
        return $this->belongsTo(User::class);
    }
}
