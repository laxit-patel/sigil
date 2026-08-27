# Sigil — spec

A number turned into one recognizable mark. Sigil takes an integer
(`0-9999`) and produces a Cistercian-style glyph — one stem plus a small
set of toggled line segments per digit place — through whatever output
format a given delivery needs: SVG, ASCII, DXF, Canvas, a font, audio,
STL, and so on.

This document is the shared source of truth. Every implementation in this
repo (PHP first, others later) builds against it and is validated against
the same fixture file, so no implementation can silently drift from
another.

## Repository structure

Sigil is **one repository**. The spec and the golden fixtures live at the
root; each language implementation is a subdirectory beside them. The point
is that the problem — what a glyph *is* — has one home, and every language
sits next to that definition rather than a copy of it.

```
SPEC.md                 # this document
fixtures/
  vectors.json          # golden test vectors every implementation must match exactly
AGENTS.md               # read-this-first for anyone writing an implementation
CLAUDE.md               # -> AGENTS.md
llms.txt                # short machine-readable project summary
README.md
LICENSE                 # MIT

sigil-php/              # PHP implementation  (laxit/sigil on Packagist)
sigil-js/    (future)   # JS/TS
sigil-py/    (future)   # Python
sigil-cli/   (future)   # standalone binary
```

Each implementation directory is self-contained — its own manifest, its own
dependencies, its own test suite — and carries an `AGENTS.md` covering what
is specific to that language. Nothing is shared between them except
`fixtures/vectors.json`, which every one of them reads.

- **`sigil-php`** — PHP, published as `laxit/sigil` on Packagist. First
  delivery target; the spec below is written PHP-first but every construct
  maps directly onto any language.
- **`sigil-js`** *(future)* — JS/TS; npm package. Natural home for the
  Canvas renderer and a `<sigil-glyph value="1234">` web component, since
  those only make sense in a browser.
- **`sigil-py`** *(future)* — Python. Useful anywhere the glyph needs to
  be generated server-side (e.g. alongside a hashed user ID) rather than
  client-side.
- **`sigil-cli`** *(future)* — a standalone CLI, most likely Go or Rust
  rather than tied to one of the ecosystems above, so it ships as a
  single dependency-free binary usable outside any package manager.

**The contract between implementations is `fixtures/vectors.json`, not this
prose.** Any implementation — PHP, JS, Python, Go, whatever comes next — is
"spec-compliant" exactly when it reproduces every vector in that file
byte-for-byte. This document explains *why* the fixtures say what they say;
the fixtures are what CI actually checks.

### Publishing from one repo

A single repo does not mean a single release. Each implementation is
versioned and tagged on its own (`php-v1.2.0`, `js-v0.3.1`), and package
registries that insist on a manifest at the repository root — Packagist
does — are fed by a **read-only split mirror**, generated from the
subdirectory rather than maintained by hand:

```bash
git subtree split --prefix=sigil-php -b php-release
git push git@github.com:laxit/sigil-php.git php-release:main
```

The mirror is an artifact. Nobody develops in it, and it never diverges,
because it is regenerated from this repo on every release.

## Core principle: model/renderer separation

```
SegmentModel  — the 5 candidate line segments + which ones each digit 0-9 turns on
Quadrant      — the 4 placements of that model (ones / tens / hundreds / thousands)
Encoder       — number -> digits -> resolved (x1,y1,x2,y2) segment list
Renderer/*    — segment list -> one specific output format
```

Renderers only ever call `Encoder.segmentsFor(number)` and `Encoder.stem()`.
They must not import or reference `SegmentModel` / `Quadrant` directly —
that constraint is what keeps new formats (and new language ports) cheap
to add without touching the number logic.

## Data model

### Candidate segments (local unit frame, 0..1)

Every quadrant is the same local square, placed at one of the four corners
of the stem and optionally mirrored. `(0,0)` is always the stem/near corner
of that quadrant; `(1,1)` is the far outer corner.

| key | from | to | shape |
|---|---|---|---|
| `top` | (0,0) | (1,0) | horizontal, near row |
| `bottom` | (0,1) | (1,1) | horizontal, far row |
| `outer` | (1,0) | (1,1) | vertical, parallel to stem |
| `diagDown` | (0,0) | (1,1) | diagonal `\` |
| `diagUp` | (0,1) | (1,0) | diagonal `/` |

### Digit → active segments

| digit | segments on |
|---|---|
| 0 | (none) |
| 1 | `top` |
| 2 | `bottom` |
| 3 | `diagDown` |
| 4 | `diagUp` |
| 5 | `top`, `bottom` |
| 6 | `outer` |
| 7 | `top`, `outer` |
| 8 | `bottom`, `outer` |
| 9 | `top`, `bottom`, `outer` |

This table is a structurally faithful, self-consistent design (place value
per quadrant, digits built by toggling a small fixed segment set, 9 forming
a closed box) — not a guaranteed stroke-for-stroke match to a specific
manuscript. It's swappable: if manuscript-exact angles are ever required,
replace only this table against a verified reference (Wikipedia's chart, or
an existing implementation such as the MIT-licensed
[rhardih/cistercian](https://github.com/rhardih/cistercian)), regenerate
`fixtures/vectors.json` from the new table, and every renderer in every
repo keeps working unchanged — none of them read the table directly.

### Known properties of this model

Two consequences fall out of the geometry and are intentional, not bugs:

- **Coincident lines at the midpoint.** A `tens` quadrant's `bottom` row and a
  `thousands` quadrant's `top` row are the same line (both at `midY`), as are
  `ones.bottom` and `hundreds.top`. A number like `7323` therefore emits two
  identical coordinate pairs. Visually this is invisible; for **laser/CNC
  output it means a doubled cut**, so a DXF consumer that cares should dedupe
  at its own layer. The encoder does not dedupe — the segment list is a
  faithful record of which digit turned which segment on, and collapsing it
  would lose the `quadrant`/`segment` labels a renderer needs.
- **The stem is always drawn**, including for `0`, which has zero segments.
  The stem is what makes an empty glyph readable as "zero" rather than blank.

### Quadrants

| name | place value | flipX (left of stem?) | flipY (below midpoint?) |
|---|---|---|---|
| `ones` | 1 | no | no |
| `tens` | 10 | yes | no |
| `hundreds` | 100 | no | yes |
| `thousands` | 1000 | yes | yes |

## Encoder algorithm

**`digitsOf(number: int) -> {ones, tens, hundreds, thousands}`**
- Validate `0 <= number <= 9999`, else raise/throw.
- `ones = number % 10`, `tens = floor(number/10) % 10`, `hundreds = floor(number/100) % 10`, `thousands = floor(number/1000) % 10`.

**`segmentsFor(number: int) -> list<{quadrant, segment, x1, y1, x2, y2}>`**
- Configurable geometry params with suggested defaults: `stemHeight=200`, `quadrantWidth=70`, `stemX=100`, `stemTopY=20`. (These defaults are baked into `fixtures/vectors.json` — an implementation can use different defaults internally, but must be able to reproduce the fixtures when given these same parameters.)
- `midY = stemTopY + stemHeight/2`, `bottomY = stemTopY + stemHeight`.
- For each quadrant: `topY = flipY ? midY : stemTopY`; `botY = flipY ? bottomY : midY`; `outerX = flipX ? stemX - quadrantWidth : stemX + quadrantWidth`.
- For each active segment key for that quadrant's digit: linearly interpolate the local `(lx,ly)` endpoints into global coordinates using `stemX`, `outerX`, `topY`, `botY`, and append `{quadrant, segment, x1, y1, x2, y2}`.

**`stem() -> (x1, y1, x2, y2)`**
- Fixed vertical line: `(stemX, stemTopY)` to `(stemX, stemTopY + stemHeight)`.

### Canonical ordering

`segmentsFor` returns a **list, not a set**, and fixture compliance is
order-sensitive. Two orderings are therefore part of the contract:

- **Quadrants**, outer loop: `ones`, `tens`, `hundreds`, `thousands` — the
  order of the quadrant table above.
- **Segments** within a quadrant, inner loop: `top`, `bottom`, `outer`,
  `diagDown`, `diagUp` — the order of the candidate-segment table above,
  *not* the order the digit table happens to list them in.

Both orders are restated in `fixtures/vectors.json` as `quadrantOrder` and
`segmentOrder` so a port can assert against them directly.

### Numeric representation

Coordinates are JSON numbers, and with the default geometry every one of them
is integral — the fixtures store `170`, never `170.0`. Implementations should
**compare numerically, not as strings**: a language that renders integral
floats as `170.0` is still compliant, one that computes `171` is not. An
implementation whose own defaults differ must still reproduce the fixtures when
handed the four parameters above.

## Renderer contract

Input: the segment list + stem line from `Encoder`. Output: one target
format. No renderer computes geometry itself beyond format-specific
rasterization (e.g. an ASCII renderer works on its own small integer grid
rather than reusing the continuous SVG-scale coordinates).

### `sigil-php` v1 renderers (build first)
- **SVG** — one `<line>` per segment plus the stem, `stroke-width` slightly heavier on the stem.
- **ASCII** — independent integer grid (suggested `4` chars per quadrant width/height), same digit/quadrant tables, `-`/`|`/`\`/`/` characters, Bresenham-style stepping for diagonals.
- **DXF (R12, LINE entities)** — same segment list, **Y sign flipped** (DXF/CAD Y grows upward, SVG-style Y here grows downward) so laser/CNC output isn't upside down.

### Later implementations / renderers (same pattern each time)
- **Canvas** *(sigil-js)* — same segment list, `ctx.moveTo/lineTo` instead of `<line>` tags; worth it once rendering hundreds of glyphs at once (e.g. an avatar list) where per-element SVG DOM nodes get expensive.
- **Font** *(sigil-js or a dedicated tool)* — bake each digit-per-quadrant combination into a custom TTF/WOFF using the Cistercian ConScript private-use-area code points (`U+EBA0`–`U+EBDF`), making the glyph copy-pasteable text for anyone with the font loaded.
- **Audio** — map active segments to notes in a short chord, play as a ~1s chime; same fingerprint concept as an SVG identicon, different sense.
- **STL** — extrude the same 2D line list into a solid embossed shape for 3D printing.
- **Web component** *(sigil-js)* — `<sigil-glyph value="1234">` wrapping the SVG renderer, so every downstream use case (avatar fingerprint, print-edition stamp, puzzle widget) is one call site.
- **CLI binary** *(sigil-cli)* — thin wrapper exposing all of the above as `sigil svg 7323`, `sigil ascii 7323`, etc., for use outside any language ecosystem.

## `sigil-php` file/module layout

```
src/
  SegmentModel.php   # data only, no logic — Cistercian\SegmentModel
  Quadrant.php       # data only — Cistercian\Quadrant
  Encoder.php        # number -> digits -> resolved segments — Cistercian\Encoder
  Renderer/
    SvgRenderer.php
    AsciiRenderer.php
    DxfRenderer.php
bin/
  demo.php
  vectors.php        # regenerates ../fixtures/vectors.json when SegmentModel changes
composer.json        # package name: laxit/sigil
```

`sigil-php/bin/vectors.php` is the tool that *generates*
`fixtures/vectors.json` whenever the digit map or default geometry changes.
The root doesn't compute the fixtures itself — it stores the output, and
every implementation is checked against it. Whichever implementation is
furthest along owns the generator; today that is PHP.

## Golden test vectors

`fixtures/vectors.json` holds real, verified `{number, digits, stem,
segments}` entries — not illustrative examples, actual encoder output —
for a set chosen to exercise every segment shape at least once (`1`, `6`,
`9` in the ones place covers `top`, `outer`, and the closed `9` box) and
every quadrant transform at least once (`10`, `100`, `1000`), plus two
composite numbers (`7323`, `9999`) as end-to-end checks. `0` confirms the
empty case (stem only, zero segments).

It is generated, never hand-edited: `sigil-php/bin/vectors.php` writes it,
and `php sigil-php/bin/vectors.php --check` exits non-zero when the committed
file has drifted from the digit map — run that in CI, alongside each
implementation's own suite.

Each implementation's test suite loads this file and asserts its own
`segmentsFor(number)` output matches the `segments` array exactly for
every vector — this is the actual compliance check, replacing any
hand-written per-language test data.

## Language ports

Only `SegmentModel`, `Quadrant`, and `Encoder` (or each language's
equivalent) need porting — under ~150 lines total, zero dependencies.
Renderers are rewritten idiomatically per implementation, not translated
line-by-line, since e.g. Canvas only makes sense in `sigil-js`.

1. **`sigil-php`** first — typed properties/readonly classes, `match`, `laxit/sigil` on Packagist.
2. **`sigil-js`** next — this is where Canvas and the web component actually belong.
3. **`sigil-py`** — wherever server-side generation is needed.
4. **`sigil-cli`** — once at least one language implementation is stable enough to wrap, or built standalone in Go/Rust if a zero-dependency binary matters more than reusing existing code.

Every port's test suite must pass `fixtures/vectors.json` before it
counts as "done" — that's the only cross-language acceptance criterion
that matters; everything else in this document is explanation for *why*
the fixtures are shaped the way they are.
