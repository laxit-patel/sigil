<?php

declare(strict_types=1);

/**
 * Resolve the autoloader whether these examples are run from a clone of the
 * repo or from inside a project's vendor/ directory.
 */

foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php'] as $autoload) {
    if (is_file($autoload)) {
        require $autoload;

        return;
    }
}

fwrite(STDERR, "Run `composer install` in php-sigil/ first.\n");
exit(1);
