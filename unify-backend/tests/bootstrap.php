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
$phpunitCandidates = ['/home/user/tools/phpunit.phar', '/tmp/phpunit.phar'];
foreach ($phpunitCandidates as $phar) {
    if (! class_exists(\PHPUnit\Framework\TestCase::class) && file_exists($phar)) {
        require $phar;
        break;
    }
}
$devdepsCandidates = ['/home/user/tools/devdeps/vendor/autoload.php', '/tmp/devdeps/vendor/autoload.php'];
foreach ($devdepsCandidates as $devAutoload) {
    if (! class_exists(\Mockery::class) && file_exists($devAutoload)) {
        require $devAutoload;
        break;
    }
}

require __DIR__ . '/../vendor/autoload.php';
