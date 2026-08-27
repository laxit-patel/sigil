<?php

declare(strict_types=1);

/**
 * Usage: php bin/demo.php <number 0-9999> [ascii|svg|dxf|all]
 *
 * Prints one glyph in the requested format. Defaults to ascii, because that
 * is the one you can read in a terminal.
 */

require __DIR__ . '/_bootstrap.php';

use Laxit\Sigil\Encoder;
use Laxit\Sigil\Renderer\AsciiRenderer;
use Laxit\Sigil\Renderer\DxfRenderer;
use Laxit\Sigil\Renderer\SvgRenderer;

$number = isset($argv[1]) ? (int) $argv[1] : 7323;
$format = strtolower($argv[2] ?? 'ascii');

$encoder = new Encoder();

$renderers = [
    'ascii' => static fn (): string => (new AsciiRenderer($encoder))->render($number),
    'svg'   => static fn (): string => (new SvgRenderer($encoder))->render($number),
    'dxf'   => static fn (): string => (new DxfRenderer($encoder))->render($number),
];

if ($format !== 'all' && !isset($renderers[$format])) {
    fwrite(STDERR, "Unknown format '{$format}'. Use ascii, svg, dxf or all.\n");
    exit(1);
}

try {
    foreach ($renderers as $name => $render) {
        if ($format !== 'all' && $format !== $name) {
            continue;
        }

        if ($format === 'all') {
            fwrite(STDOUT, "--- {$name} ---\n");
        }

        fwrite(STDOUT, $render() . "\n");
    }
} catch (InvalidArgumentException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
