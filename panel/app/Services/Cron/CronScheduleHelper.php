<?php

namespace App\Services\Cron;

use Cron\CronExpression;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class CronScheduleHelper
{
    public static function timezone(): string
    {
        return (string) config('app.timezone', 'UTC');
    }

    public static function isValidSchedule(string $schedule): bool
    {
        $parts = preg_split('/\s+/', trim($schedule), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) && count($parts) === 5;
    }

    public static function isDue(string $schedule, ?DateTimeImmutable $at = null): bool
    {
        if (! self::isValidSchedule($schedule)) {
            return false;
        }

        try {
            $expr = CronExpression::factory($schedule);
        } catch (InvalidArgumentException) {
            return false;
        }

        $at ??= new DateTimeImmutable('now', new DateTimeZone(self::timezone()));

        return $expr->isDue($at, self::timezone());
    }

    public static function nextRunAt(string $schedule, ?DateTimeImmutable $from = null): ?DateTimeImmutable
    {
        if (! self::isValidSchedule($schedule)) {
            return null;
        }

        try {
            $expr = CronExpression::factory($schedule);
        } catch (InvalidArgumentException) {
            return null;
        }

        $from ??= new DateTimeImmutable('now', new DateTimeZone(self::timezone()));
        $next = $expr->getNextRunDate($from, 0, false, self::timezone());

        return DateTimeImmutable::createFromMutable($next);
    }

    public static function currentMinuteStart(): DateTimeImmutable
    {
        $now = new DateTimeImmutable('now', new DateTimeZone(self::timezone()));

        return $now->setTime((int) $now->format('H'), (int) $now->format('i'), 0);
    }
}
