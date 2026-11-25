<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'full_name',
        'ccv',
        'card_number',
        'card_expiration',
        'order_id',
    ];

    public function orders(): BelongsTo
    {
        return $this->BelongsTo(Order::class);
    }
}
