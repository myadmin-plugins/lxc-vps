<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap file for myadmin-lxc-vps tests.
 *
 * Loads the autoloader, then the stand-ins for the MyAdmin framework services the
 * Plugin's hook handlers depend on, so those handlers can be dispatched for real.
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/stubs/framework.php';

// MyAdmin registers the vps module's settings on every request; the queue handler
// reads PREFIX back out of them when building its log messages.
register_module('vps', [
    'PREFIX' => 'vps',
    'TABLE' => 'vps',
    'TBLNAME' => 'VPS',
    'TITLE' => 'VPS Services',
]);
