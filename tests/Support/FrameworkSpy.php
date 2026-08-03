<?php

declare(strict_types=1);

namespace Detain\MyAdminLxc\Tests\Support;

/**
 * Collects the side effects the Plugin's hook handlers push out into the MyAdmin
 * framework (log lines, history entries, template renders).
 *
 * MyAdmin is not a dependency of this package, so this is what lets a test assert
 * on what a handler actually did instead of on how its source happens to be written.
 */
final class FrameworkSpy
{
    /**
     * Positional argument lists of every myadmin_log() call.
     *
     * @var array<int, array<int, mixed>>
     */
    public static $logs = [];

    /**
     * Positional argument lists of every \MyAdmin\App::history()->add() call.
     *
     * @var array<int, array<int, mixed>>
     */
    public static $history = [];

    /**
     * Template paths passed to \TFSmarty::fetch().
     *
     * @var array<int, string>
     */
    public static $renderedTemplates = [];

    /**
     * Forget everything recorded so far. Call this at the start of any test that
     * asserts on the recorded calls.
     */
    public static function reset(): void
    {
        self::$logs = [];
        self::$history = [];
        self::$renderedTemplates = [];
    }

    /**
     * Log calls made at the given level ('info', 'error', ...).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function logsWithLevel(string $level): array
    {
        return array_values(array_filter(self::$logs, static function (array $log) use ($level) {
            return isset($log[1]) && $log[1] === $level;
        }));
    }
}
