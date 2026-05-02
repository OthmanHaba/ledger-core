<?php

declare(strict_types=1);

namespace LedgerCore\Support;

final class Decimal
{
    public static function scale(): int
    {
        return (int) config('ledger.currency.scale', 8);
    }

    public static function zero(?int $scale = null): string
    {
        return self::normalize('0', $scale);
    }

    public static function normalize(string|int $amount, ?int $scale = null): string
    {
        return bcadd((string) $amount, '0', $scale ?? self::scale());
    }

    public static function add(string $left, string $right, ?int $scale = null): string
    {
        return bcadd($left, $right, $scale ?? self::scale());
    }

    public static function sub(string $left, string $right, ?int $scale = null): string
    {
        return bcsub($left, $right, $scale ?? self::scale());
    }

    public static function compare(string $left, string $right, ?int $scale = null): int
    {
        return bccomp($left, $right, $scale ?? self::scale());
    }

    public static function isZero(string $amount, ?int $scale = null): bool
    {
        return self::compare($amount, '0', $scale) === 0;
    }

    public static function isNegative(string $amount, ?int $scale = null): bool
    {
        return self::compare($amount, '0', $scale) < 0;
    }
}
