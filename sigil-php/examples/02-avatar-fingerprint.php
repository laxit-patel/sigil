<?php

declare(strict_types=1);

/**
 * Identicon-style avatars: a stable, recognizable mark per user, with no
 * image files and no upload flow.
 *
 *   php examples/02-avatar-fingerprint.php > avatars.html
 *
 * The glyph is derived from the identifier, so it is deterministic (same user,
 * same mark, forever) without storing anything. Sigil's job is only the last
 * step: number -> mark. Choosing the number is yours.
 */

require __DIR__ . '/_autoload.php';

use Cistercian\Encoder;
use Cistercian\Renderer\SvgRenderer;

$users = [
    'ada@example.com',
    'grace@example.com',
    'alan@example.com',
    'katherine@example.com',
    'edsger@example.com',
    'barbara@example.com',
];

$encoder = new Encoder();
$renderer = new SvgRenderer($encoder, stroke: 'currentColor', strokeWidth: 8, stemStrokeWidth: 10);

/**
 * Identifier -> 0-9999. crc32 is fine here: this is a visual fingerprint, not
 * a security boundary. Collisions are expected at this range -- 10k glyphs
 * cannot uniquely identify more than 10k users, and around 118 users you are
 * already at even odds of a pair sharing one (birthday problem). Use it to
 * make faces recognizable, never to prove identity.
 */
function glyphNumberFor(string $identifier): int
{
    return crc32($identifier) % 10000;
}

echo <<<HTML
<!doctype html>
<meta charset="utf-8">
<title>Sigil avatars</title>
<style>
  body { font: 15px/1.5 system-ui, sans-serif; margin: 3rem; background: #fbfbfa; color: #1a1a19; }
  ul   { list-style: none; padding: 0; display: grid; gap: 1.5rem;
         grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
  li   { text-align: center; }
  .g   { width: 88px; height: 88px; color: #2a2a28; }
  code { font-size: 13px; color: #6b6b66; }
</style>
<h1>Sigil avatars</h1>
<ul>

HTML;

foreach ($users as $user) {
    $number = glyphNumberFor($user);
    $svg = str_replace('<svg ', '<svg class="g" ', $renderer->render($number));

    printf("  <li>%s<br><code>%s</code><br><code>%04d</code></li>\n", $svg, htmlspecialchars($user), $number);
}

echo "</ul>\n";
