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
    case LOAN_ABOVE_5000         = 40;
    case LOAN_ABOVE_1500         = 22;
    case LOAN_500_TO_1500        = 20;
    case LOAN_BELOW_500          = 18;

    // expense cases
    case EXPENSE_BEAR_ABOVE_1500  = 23;
    case EXPENSE_BEAR_500_TO_1500 = 21;
    case EXPENSES_BEAR_BELOW_500  = 19;
    case EXPENSE_PAID_ABOVE_1500  = 17;
    case EXPENSE_PAID_500_TO_1500 = 15;
    case EXPENSES_PAID_BELOW_500  = 13;

    // event cases
    case EVENTS_TREASURED        = 29;
    case EVENTS_LED              = 25;
    case EVENTS_ATTENDED         = 7;
    case EVENTS_CREATED          = 3;

    // login cases
    case USER_LOGIN_COUNT        = 2;

    public static function getValue($enum): int
    {
        return match ($enum)
        {
            self::USER_LOGIN_COUNT          => 2,

            self::EVENTS_CREATED            => 3,

            self::EVENTS_ATTENDED           => 7,

            self::EVENTS_LED                => 25,

            self::EVENTS_TREASURED          => 29,

            self::EXPENSES_PAID_BELOW_500   => 13,

            self::EXPENSE_PAID_500_TO_1500  => 15,

            self::EXPENSE_PAID_ABOVE_1500   => 17,

            self::LOAN_BELOW_500            => 18,

            self::EXPENSES_BEAR_BELOW_500   => 19,

            self::LOAN_500_TO_1500          => 20,

            self::EXPENSE_BEAR_500_TO_1500  => 21,

            self::LOAN_ABOVE_1500           => 22,

            self::EXPENSE_BEAR_ABOVE_1500   => 23,

            self::SPONSOR_BELOW_200         => 24,

            self::SPONSOR_200_TO_500        => 26,

            self::SPONSOR_500_TO_1000       => 28,

            self::SPONSOR_1000_TO_1500      => 30,

            self::SPONSOR_ABOVE_1500        => 32,

            self::LOAN_ABOVE_5000           => 40,
        };
    }
}
