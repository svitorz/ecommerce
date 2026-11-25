<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = ['subtotal', 'status', 'user_id'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'orders_products')
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): hasOne
    {
        return $this->hasOne(Payment::class);
    }
}
