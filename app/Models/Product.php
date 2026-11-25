<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'price', 'stock', 'category_id',
    ];

    protected $casts = ['price' => 'float'];

    protected $appends = ['quantity', 'isFavorite'];

    protected function getIsFavoriteAttribute(): bool
    {
        return auth()->user()
            ? $this->favoritedByUsers()->where('user_id', auth()->id())->exists()
            : false;
    }

    protected function setIsFavoriteattribute($value)
    {
        $this->attributes['isFavorite'] = $value;
    }

    protected function getQuantityAttribute(): int
    {
        return $this->attributes['quantity'] ?? 1;
    }

    protected function setquantityattribute($value)
    {
        $this->attributes['quantity'] = $value;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'orders_products')
            ->withPivot('quantity', 'unit_price')
            ->withTimestamps();
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorite_products');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ProductRating::class);
    }
}
