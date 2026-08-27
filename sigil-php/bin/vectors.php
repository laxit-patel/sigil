<?php

declare(strict_types=1);

/**
 * Regenerates fixtures/vectors.json at the repo root -- the resolved Tier 2
 * output of model.json, and the contract every language implementation in
 * this repo is validated against.
 *
 * Run this whenever model.json changes, and commit the regenerated fixtures
 * in the same change. Changing them is a breaking change for every language
 * implementation at once.
 *
 * Usage:
 *   php bin/vectors.php [--check] [path/to/vectors.json]
 *
 *   --check   exit 1 if the file on disk differs from what would be written,
 *             without touching it. Intended for CI.
 *
 * The default output path can also be set with SIGIL_FIXTURES.
 */

require __DIR__ . '/_bootstrap.php';

use Laxit\Sigil\Encoder;

/**
 * 0-9 as bare ones-place vectors, so every digit's exact segment combination
 * is guaranteed by construction rather than by coincidence; 10/100/1000 to
 * isolate the quadrant transforms independently of digit shape (all digit 1,
 * already covered above); 7323/9999 as composite end-to-end checks.
 */
const NUMBERS = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 100, 1000, 7323, 9999];

$argsList = array_slice($argv, 1);
$check = in_array('--check', $argsList, true);
$paths = array_values(array_filter($argsList, static fn (string $a): bool => !str_starts_with($a, '--')));

$target = $paths[0]
    ?? getenv('SIGIL_FIXTURES')
    ?: __DIR__ . '/../../fixtures/vectors.json';

$encoder = new Encoder();

$payload = [
    'vectors' => array_map(
        static fn (int $number): array => [
            'number' => $number,
            'digits' => $encoder->digitsOf($number),
            'stem' => $encoder->stem(),
            'segments' => $encoder->segmentsFor($number),
        ],
        NUMBERS,
    ),
];

$json = json_encode(
    $payload,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
) . "\n";

if ($check) {
    $current = is_file($target) ? file_get_contents($target) : null;

    if ($current === $json) {
        fwrite(STDOUT, "vectors.json is up to date: {$target}\n");
        exit(0);
    }

    fwrite(STDERR, "vectors.json is STALE: {$target}\n");
    fwrite(STDERR, "Run `php sigil-php/bin/vectors.php` and commit the regenerated file.\n");
    exit(1);
}

$dir = dirname($target);

if (!is_dir($dir) && !mkdir($dir, 0o775, true) && !is_dir($dir)) {
    fwrite(STDERR, "Cannot create {$dir}\n");
    exit(1);
}

if (file_put_contents($target, $json) === false) {
    fwrite(STDERR, "Cannot write {$target}\n");
    exit(1);
}

$count = count(NUMBERS);
fwrite(STDOUT, "Wrote {$count} vectors to {$target}\n");
