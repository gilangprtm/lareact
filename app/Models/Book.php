<?php

namespace App\Models;

use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'isbn',
        'category_id',
        'publisher_id',
        'publish_date',
        'pages',
        'description',
        'status',
        'price',
        'is_featured',
        'language',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
        'status' => BookStatus::class
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_authors')
            ->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(BookImage::class)->orderBy('sort_order');
    }

    public function files(): HasMany
    {
        return $this->hasMany(BookFile::class);
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->orWhere('title', 'REGEXP', $search)
                    ->orWhere('isbn', 'REGEXP', $search)
                    ->orWhere('description', 'REGEXP', $search);
            });
        });

        $query->when($filters['category_id'] ?? null, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        });

        $query->when($filters['publisher_id'] ?? null, function ($query, $publisherId) {
            $query->where('publisher_id', $publisherId);
        });

        $query->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        });

        $query->when($filters['is_featured'] ?? null, function ($query, $isFeatured) {
            $query->where('is_featured', $isFeatured);
        });

        $query->when($filters['language'] ?? null, function ($query, $language) {
            $query->where('language', $language);
        });

        $query->when($filters['price_min'] ?? null, function ($query, $price) {
            $query->where('price', '>=', $price);
        });

        $query->when($filters['price_max'] ?? null, function ($query, $price) {
            $query->where('price', '<=', $price);
        });
    }

    public function scopeSorting(Builder $query, array $sorts): void
    {
        $query->when($sorts['field'] ?? null && $sorts['direction'] ?? null, function ($query) use ($sorts) {
            $query->orderBy($sorts['field'], $sorts['direction'] ?? 'asc');
        });
    }
}
