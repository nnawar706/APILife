<?php

namespace App\Enums;

enum BadgeWeight: int
{
    case SPONSOR = 6;
    case ATTENDED_EVENTS = 5;
    case EVENTS_LED = 4;
    case EVENTS_TREASURED = 3;
    case EVENTS_CREATED = 2;
    case EVENTS_EXPENSES = 1;

    public static function getValue($enum): int
    {
        return match ($enum) {
            self::SPONSOR           => 6,
            self::ATTENDED_EVENTS   => 5,
            self::EVENTS_LED        => 4,
            self::EVENTS_TREASURED  => 3,
            self::EVENTS_CREATED    => 2,
            self::EVENTS_EXPENSES   => 1,
        };
    }
}
