<p align="center">
  <img src="https://raw.githubusercontent.com/laxit-patel/sigil/main/art/umbral_sigilstone.png" alt="Umbral Sigilstone" width="400">
</p>

# 💜 Umbral Sigilstone — PHP

**laxit/sigil** — turns any integer `0-9999` into one recognizable
Cistercian-style glyph, rendered as SVG, ASCII or DXF.

> *The Umbral Sigilstone presses a number into a single struck mark: one stem
> and a handful of strokes, identical everywhere it is struck — in pixels, in
> ink, or in cut metal.*

---

```
7323  →  💜  →  svg · ascii · dxf
```

This is the PHP implementation, one language directory of the
[Sigil](https://github.com/laxit-patel/sigil) repo; the spec it implements is
[`SPEC.md`](https://github.com/laxit-patel/sigil/blob/main/SPEC.md).

```bash
composer require laxit/sigil
```

Requires PHP 8.1+. No runtime dependencies. The package ships `model.json`, the
declarative definition it loads, so it works with nothing else installed.

## Use it

```php
use Laxit\Sigil\Encoder;
use Laxit\Sigil\Renderer\SvgRenderer;
use Laxit\Sigil\Renderer\AsciiRenderer;
use Laxit\Sigil\Renderer\DxfRenderer;

$encoder = new Encoder();

echo (new SvgRenderer($encoder))->render(7323);
echo (new AsciiRenderer($encoder))->render(7323);
file_put_contents('7323.dxf', (new DxfRenderer($encoder))->render(7323));
```

```
$ php bin/demo.php 7323 ascii
    |
    |\
    | \
    |  \
|---|   \
|   |\
|   | \
|   |  \
|   |   \
```

`bin/demo.php <number> [ascii|svg|dxf|all]` renders one glyph to stdout.

## Examples

[`examples/`](examples) has four runnable, self-contained programs — input,
output and implementation for each:

| | |
|---|---|
| [`01-basics.php`](examples/01-basics.php) | The whole API — number in, segment list out, three renderers over the same list |
| [`02-avatar-fingerprint.php`](examples/02-avatar-fingerprint.php) | Identicon-style avatars: a stable mark per user, no image files, no uploads |
| [`03-wire-format.php`](examples/03-wire-format.php) | Resolve on the server, draw in the browser with no Cistercian logic client-side |
| [`04-laser-cutting.php`](examples/04-laser-cutting.php) | DXF for a laser/CNC, and the doubled-cut gotcha you must handle |

```bash
composer install
php examples/01-basics.php 7323
```

Start with [`examples/README.md`](examples/README.md), which shows each one's
output inline.

### Renderers

| Renderer | Output |
|---|---|
| `SvgRenderer` | One `<line>` per segment plus the stem, drawn slightly heavier. `viewBox` is derived from the coordinates, so it stays correct for any geometry. |
| `AsciiRenderer` | Its own small integer grid (4 cells per quadrant by default), Bresenham stepping for the diagonals. |
| `DxfRenderer` | Minimal R12 `LINE` entities. **Y is negated** — CAD Y grows upward, the encoder's Y grows downward, so a straight copy comes off a laser upside down. |

### Geometry

Defaults come from `geometryDefaults` in `model.json` — `stemHeight=200`,
`quadrantWidth=70`, `stemX=100`, `stemTopY=20`, the values the golden fixtures
were generated with. Override them per instance:

```php
$encoder = new Encoder(stemHeight: 100, quadrantWidth: 35, stemX: 50, stemTopY: 10);
```

`Encoder::stem()` returns a positional `[x1, y1, x2, y2]` array, matching the
fixture format.

## Architecture

```
model.json    the definition -- segments, digit map, quadrants, geometry   (at the repo root)
SegmentModel  typed wrapper over model.json's segments + digitMap
Quadrant      typed wrapper over model.json's places + quadrants
Encoder       the generic resolver: model.json -> (x1,y1,x2,y2) segment list
Renderer/*    segment list -> one specific output format
```

None of these classes contain the digit map. They read
[`model.json`](model.json) — a vendored copy of the canonical definition in the
[spec repo](https://github.com/laxit-patel/sigil), which diffs it against every
implementation's copy on every build so it cannot drift.
`Encoder::locateModel()` resolves it: `SIGIL_MODEL`, then beside the package
root, then one level up.

Renderers call `Encoder::segmentsFor()` and `Encoder::stem()` and **nothing
else** — they never touch `SegmentModel` or `Quadrant`. That constraint is what
makes new output formats and new language ports cheap, so it is enforced by a
test (`RendererTest::testRenderersDoNotReachIntoTheNumberLogic`), not by review.

## Tests

```bash
composer install
composer test
```

CI runs the suite on PHP 8.1 through 8.4, checks the fixtures are not stale,
and executes every example.

The suite that matters is `tests/VectorsTest.php`: it replays
[`../fixtures/vectors.json`](../fixtures/vectors.json) and asserts this
implementation reproduces every vector exactly. That is the whole definition
of "spec-compliant", and every language implementation in this repo has the
equivalent file running against the same JSON.

If the fixtures live somewhere else, point at them:

```bash
SIGIL_FIXTURES=/path/to/vectors.json composer test
```

## Changing the glyph

[`model.json`](model.json) is the one file that defines what a glyph looks
like — for this package and every other language implementation. Change it in
the [spec repo](https://github.com/laxit-patel/sigil) first, then copy it here.
The fixtures must be regenerated in the same change:

```bash
php bin/vectors.php            # rewrite ../fixtures/vectors.json
php bin/vectors.php --check    # CI: exit 1 if the committed fixtures are stale
```

Commit the regenerated fixtures alongside the change. This is a breaking
change for every language implementation at once, not just for PHP.

## License

MIT — see [`LICENSE`](LICENSE).
