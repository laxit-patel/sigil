<?php

declare(strict_types=1);

/**
 * Tier 2 as a transfer format: resolve on the server, draw on the client.
 *
 *   php examples/03-wire-format.php 7323
 *
 * The segment list is not just an internal structure -- it is a perfectly good
 * API response. A backend resolves the number; the frontend receives
 * "draw these lines" and needs no Cistercian logic, no digit map, and no copy
 * of model.json to render it. See 03-wire-format.html for the ~15-line
 * consumer.
 */

require __DIR__ . '/_autoload.php';

use Laxit\Sigil\Encoder;

$number = isset($argv[1]) ? (int) $argv[1] : 7323;

$encoder = new Encoder();

// What an endpoint like GET /glyph/7323 would return.
echo json_encode([
    'number' => $number,
    'stem' => $encoder->stem(),
    'segments' => $encoder->segmentsFor($number),
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), "\n";
