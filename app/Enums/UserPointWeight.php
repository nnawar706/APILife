<?php

namespace App\Enums;

enum UserPointWeight: int
{
    case POINT_1  = 1;
    case POINT_2  = 2;
    case POINT_5  = 5;
    case POINT_9  = 9;
    case POINT_10 = 10;
    case POINT_12 = 12;
    case POINT_13 = 13;
    case POINT_15 = 15;
    case POINT_16 = 16;
    case POINT_17 = 17;
    case POINT_20 = 20;
    case POINT_23 = 23;
    case POINT_24 = 24;
    case POINT_32 = 32;
    case POINT_35 = 35;
    case POINT_40 = 40;

    public static function getValue($enum): int
    {
        return match ($enum) {
            self::POINT_1  => 1,
            self::POINT_2  => 2,
            self::POINT_5  => 5,
            self::POINT_9  => 9,
            self::POINT_10 => 10,
            self::POINT_12 => 12,
            self::POINT_13 => 13,
            self::POINT_15 => 15,
            self::POINT_16 => 16,
            self::POINT_17 => 17,
            self::POINT_20 => 20,
            self::POINT_23 => 23,
            self::POINT_24 => 24,
            self::POINT_32 => 32,
            self::POINT_35 => 35,
            self::POINT_40 => 40,
        };
    }
}
