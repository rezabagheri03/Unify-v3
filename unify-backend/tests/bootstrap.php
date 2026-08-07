<?php

/*
 * Test bootstrap.
 *
 * Sandbox note: when PHPUnit / Mockery / Faker are not installed as composer
 * dev-dependencies (e.g. `composer install --no-dev` was used to keep the
 * workspace slim), this falls back to standalone copies under /tmp so the
 * suite still runs. On a normal `composer install` (with dev deps) these
 * fallbacks are simply skipped.
 */
if (! class_exists(\PHPUnit\Framework\TestCase::class) && file_exists('/tmp/phpunit.phar')) {
    require '/tmp/phpunit.phar';
}
if (! class_exists(\Mockery::class) && file_exists('/tmp/devdeps/vendor/autoload.php')) {
    require '/tmp/devdeps/vendor/autoload.php';
}

require __DIR__ . '/../vendor/autoload.php';
