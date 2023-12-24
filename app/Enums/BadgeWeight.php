<?php

namespace App\Enums;

enum BadgeWeight: int
{
    case SPONSOR = 5;
    case ATTENDED_EVENTS = 4;
    case EVENTS_LED = 3;
    case EVENTS_TREASURED = 2;
    case EVENTS_EXPENSES = 1;

    public static function getValue($enum): int
    {
        return match ($enum) {
            self::SPONSOR => 5,
            self::ATTENDED_EVENTS => 4,
            self::EVENTS_LED => 3,
            self::EVENTS_TREASURED => 2,
            self::EVENTS_EXPENSES => 1,
        };
    }
}
