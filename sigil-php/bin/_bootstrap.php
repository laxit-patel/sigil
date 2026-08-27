<?php

declare(strict_types=1);

/**
 * Autoloading for the bin/ scripts. Uses Composer when it is installed and
 * falls back to a PSR-4 shim otherwise, so `php bin/vectors.php` regenerates
 * the cross-repo fixtures on a clean checkout with no `composer install`.
 */

$composer = __DIR__ . '/../vendor/autoload.php';

if (is_file($composer)) {
    require $composer;

    return;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Laxit\\Sigil\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__ . '/../src/'
        . str_replace('\\', '/', substr($class, strlen($prefix)))
        . '.php';

    if (is_file($path)) {
        require $path;
    }
});
