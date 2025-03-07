<?php

namespace App\Models;

use App\Enums\PublisherStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Publisher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'logo_path',
        'status'
    ];

    protected $casts = [
        'status' => PublisherStatus::class
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->orWhere('name', 'REGEXP', $search)
                    ->orWhere('email', 'REGEXP', $search)
                    ->orWhere('phone', 'REGEXP', $search)
                    ->orWhere('city', 'REGEXP', $search)
                    ->orWhere('country', 'REGEXP', $search);
            });
        });

        $query->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        });

        $query->when($filters['city'] ?? null, function ($query, $city) {
            $query->where('city', $city);
        });

        $query->when($filters['state'] ?? null, function ($query, $state) {
            $query->where('state', $state);
        });

        $query->when($filters['country'] ?? null, function ($query, $country) {
            $query->where('country', $country);
        });

        $query->when($filters['has_books'] ?? null, function ($query) {
            $query->has('books');
        });
    }

    public function scopeSorting(Builder $query, array $sorts): void
    {
        $query->when($sorts['field'] ?? null && $sorts['direction'] ?? null, function ($query) use ($sorts) {
            $query->orderBy($sorts['field'], $sorts['direction'] ?? 'asc');
        });
    }
}
