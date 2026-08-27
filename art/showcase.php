<?php

declare(strict_types=1);

/**
 * Generates the documentation figures in art/showcase-*.svg.
 *
 *   php art/showcase.php
 *
 * Every glyph here is produced by the library, so a figure cannot drift from
 * the behaviour it documents -- the same reason art/generate.php builds the
 * project mark rather than an illustrator.
 *
 * Each file carries its own background, because a README image is shown on
 * light and dark pages alike and a bare stroke would vanish on one of them.
 */

require __DIR__ . '/../php-sigil/bin/_bootstrap.php';

use Laxit\Sigil\Encoder;

/**
 * Always the canonical model at the repo root, never the copy vendored inside
 * php-sigil. These are this repo's figures, and Encoder::locateModel() would
 * otherwise find the submodule's copy first.
 */
const MODEL = __DIR__ . '/../model.json';

const PAPER = '#fbfbfa';
const INK = '#1f1d2b';
const MUTED = '#8a8598';
const ACCENT = '#6f5fc0';
const FONT = 'ui-monospace, SFMono-Regular, Menlo, monospace';
const SANS = 'ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif';

/**
 * Draw one glyph inside a box, scaled to fit and centred.
 */
function glyph(Encoder $encoder, int $number, float $x, float $y, float $w, float $h, string $ink = INK, float $weight = 1.0, ?float $fixedScale = null, bool $alignTop = false): string
{
    $spanX = 2 * $encoder->quadrantWidth;
    $spanY = $encoder->stemHeight;

    // A caller comparing glyphs of different heights must pass one scale for
    // all of them, or fitting each to its own box hides the difference it is
    // trying to show.
    $scale = $fixedScale ?? min($w / $spanX, $h / $spanY);

    $originX = $x + ($w - $spanX * $scale) / 2 + ($encoder->stemX - ($encoder->stemX - $encoder->quadrantWidth)) * $scale;
    $originY = $alignTop ? $y : $y + ($h - $spanY * $scale) / 2;

    $map = static fn (float $gx, float $gy): array => [
        $originX + ($gx - $encoder->stemX) * $scale,
        $originY + ($gy - $encoder->stemTopY) * $scale,
    ];

    $out = '';
    [$sx1, $sy1, $sx2, $sy2] = $encoder->stem();
    [$ax, $ay] = $map((float) $sx1, (float) $sy1);
    [$bx, $by] = $map((float) $sx2, (float) $sy2);
    $out .= sprintf('<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke-width="%.2f"/>',
        $ax, $ay, $bx, $by, 5.2 * $scale * $weight);

    foreach ($encoder->segmentsFor($number) as $s) {
        [$ax, $ay] = $map((float) $s['x1'], (float) $s['y1']);
        [$bx, $by] = $map((float) $s['x2'], (float) $s['y2']);
        $out .= sprintf('<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke-width="%.2f"/>',
            $ax, $ay, $bx, $by, 4.2 * $scale * $weight);
    }

    return sprintf('<g stroke="%s" fill="none" stroke-linecap="round">%s</g>', $ink, $out);
}

function text(string $s, float $x, float $y, string $fill = MUTED, float $size = 13, string $family = FONT, string $anchor = 'middle', string $weight = 'normal'): string
{
    return sprintf(
        '<text x="%.1f" y="%.1f" fill="%s" font-size="%.1f" font-family="%s" text-anchor="%s" font-weight="%s">%s</text>',
        $x, $y, $fill, $size, $family, $anchor, $weight, htmlspecialchars($s, ENT_XML1)
    );
}

function svg(float $w, float $h, string $body): string
{
    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$.0f %2$.0f" width="%1$.0f" height="%2$.0f" role="img">'
        . '<rect width="%1$.0f" height="%2$.0f" rx="10" fill="%3$s"/>%4$s</svg>' . "\n",
        $w, $h, PAPER, $body
    );
}

function write(string $name, string $content): void
{
    file_put_contents(__DIR__ . '/' . $name, $content);
    fwrite(STDOUT, sprintf("  %-28s %6d bytes\n", $name, strlen($content)));
}

$encoder = new Encoder(MODEL);

// ---------------------------------------------------------------- digits ---
$cellW = 92;
$body = text('One digit, ten shapes', 30, 40, INK, 17, SANS, 'start', '600')
    . text('the ones place, right of the stem', 30, 62, MUTED, 13, SANS, 'start');

for ($d = 0; $d <= 9; $d++) {
    $x = 30 + $d * $cellW;
    $body .= glyph($encoder, $d, $x, 84, $cellW - 18, 120);
    $body .= text((string) $d, $x + ($cellW - 18) / 2, 232, ACCENT, 15);
}
write('showcase-digits.svg', svg(30 + 10 * $cellW, 258, $body));

// ---------------------------------------------------------------- places ---
$body = text('Each corner is a place', 30, 40, INK, 17, SANS, 'start', '600')
    . text('the same digit 1, moved around the stem', 30, 62, MUTED, 13, SANS, 'start');

foreach ([[1, 'ones', '1'], [10, 'tens', '10'], [100, 'hundreds', '100'], [1000, 'thousands', '1000']] as $i => [$n, $place, $label]) {
    $x = 30 + $i * 150;
    $body .= glyph($encoder, $n, $x, 84, 132, 150);
    $body .= text($place, $x + 66, 262, ACCENT, 14, FONT);
    $body .= text($label, $x + 66, 282, MUTED, 13);
}
write('showcase-places.svg', svg(30 + 4 * 150, 306, $body));

// -------------------------------------------------------------- anatomy ---
$cell = 108;
$gap = 46;
$body = text('7323, one quadrant per digit', 30, 40, INK, 17, SANS, 'start', '600')
    . text('four digits, four corners, one mark', 30, 62, MUTED, 13, SANS, 'start');

$x = 30;
foreach ([[7000, '7000'], [300, '300'], [20, '20'], [3, '3']] as [$n, $label]) {
    $body .= glyph($encoder, $n, $x, 88, $cell, 150, MUTED, 0.9);
    $body .= text($label, $x + $cell / 2, 268, MUTED, 13);
    $body .= text('+', $x + $cell + $gap / 2, 170, MUTED, 19, SANS);
    $x += $cell + $gap;
}

// The last '+' drawn above is really the '=' position; overpaint it.
$body .= sprintf('<rect x="%.1f" y="150" width="30" height="30" fill="%s"/>', $x - $gap / 2 - 15, PAPER)
    . text('=', $x - $gap / 2, 170, MUTED, 19, SANS)
    . glyph($encoder, 7323, $x, 88, $cell + 16, 150, INK, 1.15)
    . text('7323', $x + ($cell + 16) / 2, 268, ACCENT, 15);

write('showcase-anatomy.svg', svg($x + $cell + 16 + 30, 292, $body));

// ---------------------------------------------------------------- rows ----
$sets = [[2, 9999], [3, 123456], [4, 12345678], [5, 1234567890]];
$tallest = new Encoder(MODEL, rows: 5);
$boxH = 290;
$shared = $boxH / $tallest->stemHeight;   // one scale for all four

$body = text('Past four digits, the stem grows', 30, 40, INK, 17, SANS, 'start', '600')
    . text('more rows, still one mark — not several glyphs side by side', 30, 62, MUTED, 13, SANS, 'start');

foreach ($sets as $i => [$rows, $n]) {
    $wide = new Encoder(MODEL, rows: $rows);
    $x = 30 + $i * 176;
    $body .= glyph($wide, $n, $x, 92, 156, $boxH, INK, 1.0, $shared, true);
    $body .= text(number_format($n), $x + 78, 412, ACCENT, 14);
    $body .= text($rows . ' rows, ' . 2 * $rows . ' places', $x + 78, 432, MUTED, 12, SANS);
}
write('showcase-rows.svg', svg(30 + 4 * 176, 456, $body));

// -------------------------------------------------------------- avatars ---
$users = ['ada@example.com', 'grace@example.com', 'alan@example.com',
          'katherine@example.com', 'edsger@example.com', 'barbara@example.com'];

$body = text('Identicon avatars', 30, 40, INK, 17, SANS, 'start', '600')
    . text('crc32(identifier) % 10000 — stable, and nothing stored', 30, 62, MUTED, 13, SANS, 'start');

foreach ($users as $i => $user) {
    $n = crc32($user) % 10000;
    $x = 30 + $i * 172;
    $body .= glyph($encoder, $n, $x, 88, 150, 150);
    $body .= text($user, $x + 75, 268, MUTED, 12);
    $body .= text(sprintf('%04d', $n), $x + 75, 288, ACCENT, 13);
}
write('showcase-avatars.svg', svg(30 + 6 * 172, 312, $body));
