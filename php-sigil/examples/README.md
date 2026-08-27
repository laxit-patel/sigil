# Examples

Runnable, each one self-contained. From `php-sigil/`:

```bash
composer install
php examples/01-basics.php 7323
```

| File | Shows |
|---|---|
| [`01-basics.php`](01-basics.php) | The whole API — number in, segment list out, three renderers over the same list |
| [`02-avatar-fingerprint.php`](02-avatar-fingerprint.php) | Identicon-style avatars: a stable mark per user, no image files, no uploads |
| [`03-wire-format.php`](03-wire-format.php) + [`.html`](03-wire-format.html) | Resolve on the server, draw in the browser with no Cistercian logic client-side |
| [`04-laser-cutting.php`](04-laser-cutting.php) | DXF for a laser/CNC, and the doubled-cut gotcha you must handle |

---

## 01 — basics

Everything the library does. One `Encoder` turns a number into a list of line
segments; each renderer turns that same list into one format.

```php
$encoder = new Encoder();

$encoder->digitsOf(7323);      // ['ones'=>3, 'tens'=>2, 'hundreds'=>3, 'thousands'=>7]
$encoder->stem();              // [100, 20, 100, 220]
$encoder->segmentsFor(7323);   // 5 segments, see below

(new AsciiRenderer($encoder))->render(7323);
(new SvgRenderer($encoder))->render(7323);
(new DxfRenderer($encoder))->render(7323);
```

`php examples/01-basics.php 7323`:

```
number:  7323
digits:  {"ones":3,"tens":2,"hundreds":3,"thousands":7}
stem:    [100,20,100,220]
segments: 5

--- segments ---
  ones      diagDown (100, 20) -> (170,120)
  tens      bottom   (100,120) -> ( 30,120)
  hundreds  diagDown (100,120) -> (170,220)
  thousands top      (100,120) -> ( 30,120)
  thousands outer    ( 30,120) -> ( 30,220)

--- ascii ---
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

Read the glyph by quadrant: top-right is ones, top-left tens, bottom-right
hundreds, bottom-left thousands.

---

## 02 — avatar fingerprints

A recognizable mark per user with no image files, no upload flow and nothing
stored: derive the number from the identifier, and the same user always gets
the same glyph.

```php
$number = crc32($identifier) % 10000;
$svg = (new SvgRenderer($encoder, stroke: 'currentColor'))->render($number);
```

`php examples/02-avatar-fingerprint.php > avatars.html` writes a contact sheet:

```
ada@example.com        -> 4112
grace@example.com      -> 7555
alan@example.com       -> 4944
katherine@example.com  -> 0401
edsger@example.com     -> 6855
barbara@example.com    -> 9614
```

`stroke: 'currentColor'` makes the glyph inherit surrounding text colour, so
it themes for free.

**Collisions are expected.** 10,000 glyphs cannot uniquely identify more than
10,000 users, and by ~118 users you are at even odds of a shared pair
(birthday problem). This makes faces recognizable; it never proves identity.

---

## 03 — wire format

The segment list is a perfectly good API response. A backend resolves the
number; the client receives *"draw these lines"* and needs no digit map, no
quadrant table and no copy of `model.json`.

`php examples/03-wire-format.php 7323` — what `GET /glyph/7323` would return:

```json
{
    "number": 7323,
    "stem": [100, 20, 100, 220],
    "segments": [
        { "quadrant": "ones", "segment": "diagDown", "x1": 100, "y1": 20, "x2": 170, "y2": 120 },
        ...
    ]
}
```

[`03-wire-format.html`](03-wire-format.html) is the entire consumer — about
fifteen lines of `createElementNS`. Open it in a browser. This is Tier 2 from
[`../../SPEC.md`](../../SPEC.md), and it is why a PHP backend and a JS frontend
never need to agree on anything but line coordinates.

---

## 04 — laser cutting

DXF output with the one gotcha that matters on a physical machine.

Quadrants meeting at the vertical midpoint **share an edge** — a `tens`
quadrant's `bottom` row *is* a `thousands` quadrant's `top` row. The encoder
emits both, deliberately: the segment list records which digit turned which
segment on, and collapsing it would lose the labels renderers need. Invisible
on screen; on a laser it is a second pass over the same cut.

`php examples/04-laser-cutting.php 9999`:

```
number 9999: 12 segments, 2 coincident pair(s)

  DOUBLED  (100,120)-(170,120)  drawn by ones.bottom and hundreds.top
  DOUBLED  (100,120)-(30,120)   drawn by tens.bottom and thousands.top

  9999-raw.dxf       13 LINE entities
  9999-deduped.dxf   11 LINE entities
```

Dedupe at your layer before sending to CAM, as this example does. Also note
the DXF renderer **negates Y**: CAD Y grows upward, the encoder's grows
downward, so a straight copy comes off the bed upside down.
