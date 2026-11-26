<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    protected $casts = ['price' => 'float', 'isFavorite' => 'boolean', 'average_rating' => 'float'];

    /**
     * Marca `isFavorite` nos produtos da consulta, em uma única subconsulta.
     */
    public function scopeWithFavoriteFlag(Builder $query, ?int $userId): void
    {
        if ($userId === null) {
            $query->selectRaw('products.*, false as "isFavorite"');

            return;
        }

        $query->withExists([
            'favoritedByUsers as isFavorite' => fn (Builder $q) => $q->where('users.id', $userId),
        ]);
    }

    /**
     * Traz a média (`average_rating`) e a contagem (`ratings_count`) das avaliações.
     */
    public function scopeWithRatingSummary(Builder $query): void
    {
        $query->withAvg('ratings as average_rating', 'rating')->withCount('ratings');
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
