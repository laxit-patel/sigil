<?php

declare(strict_types=1);

/**
 * The whole API, in one file.
 *
 *   php examples/01-basics.php [number]
 *
 * One Encoder resolves a number into a list of line segments; each renderer
 * turns that same list into one output format. Nothing else to learn.
 */

require __DIR__ . '/_autoload.php';

use Laxit\Sigil\Encoder;
use Laxit\Sigil\Renderer\AsciiRenderer;
use Laxit\Sigil\Renderer\DxfRenderer;
use Laxit\Sigil\Renderer\SvgRenderer;

$number = isset($argv[1]) ? (int) $argv[1] : 7323;

$encoder = new Encoder();

// --- Input -------------------------------------------------------------
echo "number:  {$number}\n";
echo "digits:  ", json_encode($encoder->digitsOf($number)), "\n";
echo "stem:    ", json_encode($encoder->stem()), "\n";
echo "segments: ", count($encoder->segmentsFor($number)), "\n\n";

// --- The segment list: what every renderer consumes ---------------------
echo "--- segments ---\n";
foreach ($encoder->segmentsFor($number) as $s) {
    printf(
        "  %-9s %-8s (%3d,%3d) -> (%3d,%3d)\n",
        $s['quadrant'], $s['segment'], $s['x1'], $s['y1'], $s['x2'], $s['y2']
    );
}

// --- Three renderers over that same list --------------------------------
echo "\n--- ascii ---\n", (new AsciiRenderer($encoder))->render($number), "\n";

echo "\n--- svg ---\n", (new SvgRenderer($encoder))->render($number), "\n";

$dxf = (new DxfRenderer($encoder))->render($number);
echo "\n--- dxf ---\n", substr_count($dxf, "\r\nLINE\r\n"), " LINE entities, ",
    strlen($dxf), " bytes (Y negated for CAD)\n";
