<?php

namespace App\Enums;

enum BadgeWeight: int
{
    // sponsor cases
    case SPONSOR_ABOVE_1500      = 32;
    case SPONSOR_1000_TO_1500    = 30;
    case SPONSOR_500_TO_1000     = 28;
    case SPONSOR_200_TO_500      = 26;
    case SPONSOR_BELOW_200       = 24;

    // user loan
    case LOAN_ABOVE_1500         = 22;
    case LOAN_500_TO_1500        = 20;
    case LOAN_BELOW_500          = 18;

    // expense cases
    case EXPENSE_BEAR_ABOVE_1500  = 16;
    case EXPENSE_BEAR_500_TO_1500 = 14;
    case EXPENSES_BEAR_BELOW_500  = 12;
    case EXPENSE_PAID_ABOVE_1500  = 10;
    case EXPENSE_PAID_500_TO_1500 = 8;
    case EXPENSES_PAID_BELOW_500  = 6;

    // event cases
    case EVENTS_TREASURED        = 5;
    case EVENTS_LED              = 4;
    case EVENTS_ATTENDED         = 3;
    case EVENTS_CREATED          = 2;

    // login cases
    case USER_LOGIN_COUNT        = 1;

    public static function getValue($enum): int
    {
        return match ($enum)
        {
            self::USER_LOGIN_COUNT          => 1,

            self::EVENTS_CREATED            => 2,

            self::EVENTS_ATTENDED           => 3,

            self::EVENTS_LED                => 4,

            self::EVENTS_TREASURED          => 5,

            self::EXPENSES_PAID_BELOW_500   => 6,

            self::EXPENSE_PAID_500_TO_1500  => 8,

            self::EXPENSE_PAID_ABOVE_1500   => 10,

            self::EXPENSES_BEAR_BELOW_500   => 12,

            self::EXPENSE_BEAR_500_TO_1500  => 14,

            self::EXPENSE_BEAR_ABOVE_1500   => 16,

            self::LOAN_BELOW_500            => 18,

            self::LOAN_500_TO_1500          => 20,

            self::LOAN_ABOVE_1500           => 22,

            self::SPONSOR_BELOW_200         => 24,

            self::SPONSOR_200_TO_500        => 26,

            self::SPONSOR_500_TO_1000       => 28,

            self::SPONSOR_1000_TO_1500      => 30,

            self::SPONSOR_ABOVE_1500        => 32
        };
    }
}
