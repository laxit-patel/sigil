<?php

declare(strict_types=1);

/**
 * DXF for a laser cutter or CNC router -- and the one gotcha that matters
 * when the output drives a physical machine.
 *
 *   php examples/04-laser-cutting.php 7323
 *
 * Quadrants that meet at the vertical midpoint share an edge: a `tens`
 * quadrant's `bottom` row IS a `thousands` quadrant's `top` row. The encoder
 * emits both, because the segment list is a faithful record of which digit
 * turned which segment on -- collapsing it would lose the labels a renderer
 * needs. On screen that is invisible. On a laser it is a doubled pass over
 * the same line: a deeper kerf, a scorch mark, or a fire risk onmaterials like
 * acrylic. Dedupe before you send it to CAM.
 */

require __DIR__ . '/_autoload.php';

use Cistercian\Encoder;
use Cistercian\Renderer\DxfRenderer;

$number = isset($argv[1]) ? (int) $argv[1] : 7323;

$encoder = new Encoder();
$segments = $encoder->segmentsFor($number);

// --- Find coincident lines ----------------------------------------------
$seen = [];
$coincident = [];

foreach ($segments as $s) {
    // Same line regardless of which end is listed first.
    $ends = [[$s['x1'], $s['y1']], [$s['x2'], $s['y2']]];
    sort($ends);
    $key = json_encode($ends);

    if (isset($seen[$key])) {
        $coincident[] = [$seen[$key], $s];
    }

    $seen[$key] = $s;
}

printf("number %d: %d segments, %d coincident pair(s)\n\n", $number, count($segments), count($coincident));

foreach ($coincident as [$a, $b]) {
    printf(
        "  DOUBLED  (%d,%d)-(%d,%d)  drawn by %s.%s and %s.%s\n",
        $a['x1'], $a['y1'], $a['x2'], $a['y2'],
        $a['quadrant'], $a['segment'], $b['quadrant'], $b['segment']
    );
}

// --- Emit, then strip the duplicate cuts --------------------------------
$dxf = (new DxfRenderer($encoder))->render($number);

$out = __DIR__ . '/output';
if (!is_dir($out)) {
    mkdir($out, 0o775, true);
}

file_put_contents("{$out}/{$number}-raw.dxf", $dxf);
file_put_contents("{$out}/{$number}-deduped.dxf", $deduped = dedupeLines($dxf));

printf(
    "\n  %s  %d LINE entities\n  %s  %d LINE entities\n",
    "{$number}-raw.dxf     ", substr_count($dxf, "\r\nLINE\r\n"),
    "{$number}-deduped.dxf ", substr_count($deduped, "\r\nLINE\r\n"),
);

/**
 * Drop repeated LINE entities from an R12 DXF. Done here at the consumer's
 * layer, exactly where the spec says it belongs -- the encoder stays a
 * faithful record and the machine gets one pass per cut.
 */
function dedupeLines(string $dxf): string
{
    $header = "0\r\nSECTION\r\n2\r\nENTITIES\r\n";
    $footer = "0\r\nENDSEC\r\n0\r\nEOF\r\n";

    $body = substr($dxf, strlen($header), -strlen($footer));
    $entities = array_filter(explode("0\r\nLINE\r\n", $body));

    $unique = array_map(
        static fn (string $entity): string => "0\r\nLINE\r\n" . $entity,
        array_unique($entities),
    );

    return $header . implode('', $unique) . $footer;
}
