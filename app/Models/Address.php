<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'state',
        'city',
        'street',
        'zip_code',
        'number',
        'user_id',
    ];

    public function users(): BelongsTo
    {
        return $this->BelongsTo(User::class);
    }
}
