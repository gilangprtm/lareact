<?php

namespace App\Enums;

enum BookStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case OUT_OF_STOCK = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::OUT_OF_STOCK => 'Out of Stock',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
            self::OUT_OF_STOCK => 'yellow',
        };
    }

    public static function forSelect(): array
    {
        return collect(self::cases())->map(fn($status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
        ])->toArray();
    }
}
