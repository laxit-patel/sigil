<?php

declare(strict_types=1);

/**
 * Generates the project mark: art/umbral_sigilstone.svg
 *
 *   php art/generate.php && <rasterise to art/obsidian_sigilstone.png at 500x500>
 *
 * The logo is a real Sigil glyph, produced by the library it advertises --
 * 4444: the same segment (diagUp) shown under all four quadrant transforms,
 * so the diagonals fan out purely because of the mirroring. One shape, four
 * placements, plus the stem every glyph shares.
 *
 * 9999 was the other contender and is the more complete statement of the
 * system, but its closed boxes swallow the stem entirely -- and the stem is
 * what makes even 0 readable. A mark that hides it is the wrong mark.
 *
 * Pass a number and an output path to try alternatives:
 *   php art/generate.php 4444 /tmp/try.svg
 */

require __DIR__ . '/../php-sigil/bin/_bootstrap.php';

use Laxit\Sigil\Encoder;

/**
 * Always the canonical model at the repo root, never the copy vendored inside
 * php-sigil. These are this repo's figures, and Encoder::locateModel() would
 * otherwise find the submodule's copy first.
 */
const MODEL = __DIR__ . '/../model.json';

$number = isset($argv[1]) ? (int) $argv[1] : 4444;
$outfile = $argv[2] ?? __DIR__ . '/umbral_sigilstone.svg';

$encoder = new Encoder(MODEL);

/** Fit the glyph's own coordinate space into the medallion. */
$lines = [];
[$sx1, $sy1, $sx2, $sy2] = $encoder->stem();
$lines[] = ['x1' => $sx1, 'y1' => $sy1, 'x2' => $sx2, 'y2' => $sy2, 'w' => 12];

foreach ($encoder->segmentsFor($number) as $s) {
    $lines[] = ['x1' => $s['x1'], 'y1' => $s['y1'], 'x2' => $s['x2'], 'y2' => $s['y2'], 'w' => 10];
}

// Glyph space is x 30..170, y 20..220 -> centre it on the disc, slightly high
// to leave room for the pedestal.
$scale = 1.02;
$offsetX = 250 - 100 * $scale;   // stemX = 100
$offsetY = 232 - 120 * $scale;   // midY  = 120

$strokes = '';
foreach ($lines as $l) {
    $strokes .= sprintf(
        '<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke-width="%.2f"/>',
        $offsetX + $l['x1'] * $scale,
        $offsetY + $l['y1'] * $scale,
        $offsetX + $l['x2'] * $scale,
        $offsetY + $l['y2'] * $scale,
        $l['w'] * $scale,
    );
}

/** Obsidian shards floating around the mark. */
$shards = '';
$scatter = [
    [112, 152, 19, -18, .95], [386, 140, 15,  24, .85], [ 98, 302, 14, 35, .9],
    [396, 292, 17, -12, .85], [146,  92, 11,  14, .7],  [352,  88, 12, -28, .72],
    [122, 376, 13,  20, .78], [374, 368, 11, -16, .7],  [ 88, 228,  9,  8,  .6],
    [412, 214, 10, -22, .58], [200,  76,  9, 30, .55],  [300,  70,  8, -14, .5],
];

foreach ($scatter as [$cx, $cy, $r, $rot, $opacity]) {
    $shards .= sprintf(
        '<g transform="translate(%d %d) rotate(%d)" opacity="%.2f">'
        . '<polygon points="0,%.1f %.1f,0 0,%.1f %.1f,0" fill="url(#shard)"/>'
        . '<polygon points="0,%.1f %.1f,0 0,0" fill="#b9c6ff" opacity=".35"/>'
        . '</g>',
        $cx, $cy, $rot, $opacity,
        -$r, $r * .68, $r, -$r * .68,
        -$r, $r * .68,
    );
}

$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500" width="500" height="500">
  <defs>
    <!-- Gold bezel: light from the upper left, as on the sibling packages. -->
    <linearGradient id="bezel" x1="0.15" y1="0" x2="0.85" y2="1">
      <stop offset="0"    stop-color="#f6e6a8"/>
      <stop offset="0.18" stop-color="#e3c46b"/>
      <stop offset="0.45" stop-color="#c9992f"/>
      <stop offset="0.72" stop-color="#a2751f"/>
      <stop offset="1"    stop-color="#7d5613"/>
    </linearGradient>

    <!-- Obsidian interior: violet-black, lit from the upper left. -->
    <radialGradient id="disc" cx="0.38" cy="0.32" r="0.82">
      <stop offset="0"    stop-color="#3a3157"/>
      <stop offset="0.42" stop-color="#221c38"/>
      <stop offset="0.78" stop-color="#120e20"/>
      <stop offset="1"    stop-color="#08060f"/>
    </radialGradient>

    <linearGradient id="tabletFace" x1="0.2" y1="0" x2="0.8" y2="1">
      <stop offset="0"   stop-color="#4b3f6f"/>
      <stop offset="0.5" stop-color="#2a2244"/>
      <stop offset="1"   stop-color="#171128"/>
    </linearGradient>

    <linearGradient id="tabletFacet" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0"   stop-color="#6a5a95" stop-opacity=".85"/>
      <stop offset="1"   stop-color="#2b2246" stop-opacity=".2"/>
    </linearGradient>

    <linearGradient id="shard" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0"   stop-color="#8d7fc4"/>
      <stop offset="1"   stop-color="#2d2547"/>
    </linearGradient>

    <linearGradient id="plinth" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0"   stop-color="#7d7a8c"/>
      <stop offset="0.5" stop-color="#4c495c"/>
      <stop offset="1"   stop-color="#26243180"/>
    </linearGradient>

    <radialGradient id="halo" cx="0.5" cy="0.5" r="0.5">
      <stop offset="0"    stop-color="#a9b8ff" stop-opacity=".40"/>
      <stop offset="0.55" stop-color="#7d6fd0" stop-opacity=".14"/>
      <stop offset="1"    stop-color="#000000" stop-opacity="0"/>
    </radialGradient>

    <filter id="markGlow" x="-60%" y="-60%" width="220%" height="220%">
      <feGaussianBlur stdDeviation="7" result="b"/>
      <feMerge><feMergeNode in="b"/><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
    </filter>

    <filter id="softGlow" x="-50%" y="-50%" width="200%" height="200%">
      <feGaussianBlur stdDeviation="10"/>
    </filter>

    <clipPath id="discClip"><circle cx="250" cy="250" r="218"/></clipPath>
  </defs>

  <!-- Disc -->
  <circle cx="250" cy="250" r="218" fill="url(#disc)"/>

  <g clip-path="url(#discClip)">
    <!-- Halo behind the mark -->
    <circle cx="250" cy="232" r="170" fill="url(#halo)"/>

    <!-- Faceted obsidian tablet the mark is struck into -->
    <g>
      <polygon points="250,74 372,146 372,318 250,392 128,318 128,146"
               fill="url(#tabletFace)" stroke="#8877c4" stroke-width="2.4" stroke-opacity=".8"/>
      <polygon points="250,74 372,146 250,206 128,146" fill="url(#tabletFacet)" opacity=".75"/>
      <polygon points="128,146 250,206 250,392 128,318" fill="#000000" opacity=".22"/>
      <polygon points="372,146 372,318 250,392 250,206" fill="#000000" opacity=".34"/>
      <polyline points="128,146 250,206 372,146" fill="none" stroke="#b6a6ea" stroke-width="1.8" stroke-opacity=".7"/>
      <line x1="250" y1="206" x2="250" y2="392" stroke="#b6a6ea" stroke-width="1.3" stroke-opacity=".4"/>
    </g>

    $shards

    <!-- Plinth -->
    <g opacity=".92">
      <ellipse cx="250" cy="396" rx="96" ry="20" fill="url(#plinth)"/>
      <ellipse cx="250" cy="390" rx="96" ry="20" fill="#5b5870"/>
      <ellipse cx="250" cy="388" rx="78" ry="15" fill="#3b3950"/>
      <path d="M250 378 l11 10 -11 10 -11 -10 z" fill="#b9c6ff" opacity=".9"/>
      <path d="M250 378 l11 10 -11 0 z" fill="#ffffff" opacity=".45"/>
    </g>

    <!-- The mark: a real Sigil glyph for 1234 -->
    <g filter="url(#markGlow)" stroke="#eef1ff" stroke-linecap="round" fill="none">
      $strokes
    </g>
    <g stroke="#ffffff" stroke-linecap="round" fill="none" opacity=".75">
      $strokes
    </g>

    <!-- Inner shadow at the rim -->
    <circle cx="250" cy="250" r="218" fill="none" stroke="#000000" stroke-width="26" stroke-opacity=".35" filter="url(#softGlow)"/>
  </g>

  <!-- Bezel -->
  <circle cx="250" cy="250" r="231" fill="none" stroke="url(#bezel)" stroke-width="26"/>
  <circle cx="250" cy="250" r="244" fill="none" stroke="#6a4a10" stroke-width="2" stroke-opacity=".8"/>
  <circle cx="250" cy="250" r="218" fill="none" stroke="#5e410e" stroke-width="2.5" stroke-opacity=".9"/>
  <path d="M 78 118 A 231 231 0 0 1 250 19" fill="none" stroke="#fff6d2" stroke-width="7"
        stroke-opacity=".55" stroke-linecap="round"/>
</svg>

SVG;

file_put_contents($outfile, $svg);

fwrite(STDOUT, "Wrote {$outfile} (glyph {$number})\n");
