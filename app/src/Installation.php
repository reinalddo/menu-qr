<?php

declare(strict_types=1);

namespace MenuQr;

final class Installation
{
    public static function isComplete(): bool
    {
        return (bool) Db::pdo()->query('SELECT EXISTS(SELECT 1 FROM admins)')->fetchColumn();
    }

    /** Box printed in the container logs, the only place where codes are shown. */
    public static function banner(array $lines): string
    {
        $rule = str_repeat('=', 60);
        return $rule . "\n" . implode("\n", array_map(static fn (string $line): string => ' ' . $line, $lines)) . "\n" . $rule . "\n";
    }
}
