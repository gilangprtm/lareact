<?php

namespace App\Enums;

enum MessageType
{
    case CREATED;
    case UPDATED;
    case DELETED;
    case ERROR;

    public function message(string $resource, ?string $error = null): string
    {
        return match ($this) {
            self::CREATED => "$resource has been created successfully",
            self::UPDATED => "$resource has been updated successfully",
            self::DELETED => "$resource has been deleted successfully",
            self::ERROR => "Failed to process $resource " . ($error ?? ''),
        };
    }
}
