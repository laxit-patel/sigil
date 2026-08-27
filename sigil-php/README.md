# laxit/sigil

PHP implementation of [Sigil](..) — turn an integer `0-9999` into one
recognizable Cistercian-style glyph, rendered as SVG, ASCII or DXF.

This is one language directory of the Sigil repo; the spec it implements is
[`../SPEC.md`](../SPEC.md).

```bash
composer require laxit/sigil
```

Packagist is fed by a read-only `git subtree split` mirror of this directory —
develop here, never in the mirror. See *Publishing from one repo* in
[`../SPEC.md`](../SPEC.md).

Requires PHP 8.1+. No runtime dependencies.

## Use it

```php
use Cistercian\Encoder;
use Cistercian\Renderer\SvgRenderer;
use Cistercian\Renderer\AsciiRenderer;
use Cistercian\Renderer\DxfRenderer;

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

### Renderers

| Renderer | Output |
|---|---|
| `SvgRenderer` | One `<line>` per segment plus the stem, drawn slightly heavier. `viewBox` is derived from the coordinates, so it stays correct for any geometry. |
| `AsciiRenderer` | Its own small integer grid (4 cells per quadrant by default), Bresenham stepping for the diagonals. |
| `DxfRenderer` | Minimal R12 `LINE` entities. **Y is negated** — CAD Y grows upward, the encoder's Y grows downward, so a straight copy comes off a laser upside down. |

### Geometry

Defaults are `stemHeight=200`, `quadrantWidth=70`, `stemX=100`, `stemTopY=20`
— the values baked into the golden fixtures. Override them per instance:

```php
$encoder = new Encoder(stemHeight: 100, quadrantWidth: 35, stemX: 50, stemTopY: 10);
```

## Architecture

```
SegmentModel  the 5 candidate segments + which ones each digit 0-9 turns on   (data only)
Quadrant      the 4 placements of that model: ones / tens / hundreds / thousands (data only)
Encoder       number -> digits -> resolved (x1,y1,x2,y2) segment list
Renderer/*    segment list -> one specific output format
```

Renderers call `Encoder::segmentsFor()` and `Encoder::stem()` and **nothing
else** — they never touch `SegmentModel` or `Quadrant`. That constraint is what
makes new output formats and new language ports cheap, so it is enforced by a
test (`RendererTest::testRenderersDoNotReachIntoTheNumberLogic`), not by review.

## Tests

```bash
composer install
composer test
```

The suite that matters is `tests/VectorsTest.php`: it replays
[`../fixtures/vectors.json`](../fixtures/vectors.json) and asserts this
implementation reproduces every vector exactly. That is the whole definition
of "spec-compliant", and every language implementation in this repo has the
equivalent file running against the same JSON.

If the fixtures live somewhere else — a split mirror, say — point at them:

```bash
SIGIL_FIXTURES=/path/to/vectors.json composer test
```

## Changing the glyph

`SegmentModel::DIGITS` is the one table that defines what a glyph looks like.
If you change it — or the default geometry — the fixtures must be regenerated
in the same change:

```bash
php bin/vectors.php            # rewrite ../fixtures/vectors.json
php bin/vectors.php --check    # CI: exit 1 if the committed fixtures are stale
```

Commit the regenerated fixtures alongside the change. This is a breaking
change for every language implementation at once, not just for PHP.

## License

MIT — see [`LICENSE`](LICENSE).
