# Sigil

[![CI](https://github.com/laxit/sigil/actions/workflows/ci.yml/badge.svg)](https://github.com/laxit/sigil/actions/workflows/ci.yml)

A number turned into one recognizable mark.

Sigil takes an integer `0-9999` and produces a Cistercian-style glyph — one
vertical stem plus a small set of toggled line segments, one quadrant per digit
place — in whatever output format a given delivery needs: SVG, ASCII, DXF,
Canvas, a font, audio, STL.

```
  7323          9999            0
    |         |---|---|         |
    |\        |   |   |         |
    | \       |   |   |         |
    |  \      |   |   |         |
|---|   \     |---|---|         |
|   |\        |   |   |         |
|   | \       |   |   |         |
|   |  \      |   |   |         |
|   |   \     |---|---|         |
```

**One repo, many languages.** The spec and the golden test vectors live at
the root; each language implementation is a subdirectory beside them. The
problem — what a glyph *is* — has one home, and every language sits next to
that definition rather than a copy of it.

| Path | What it is |
|---|---|
| [`SPEC.md`](SPEC.md) | The specification. Data model, digit→segment table, quadrant transforms, encoder algorithm, renderer contract. |
| [`model.json`](model.json) | **Tier 1 — the definition.** Segment shapes, digit map, quadrant transforms, default geometry. Every implementation *loads* this; none retype it. |
| [`fixtures/vectors.json`](fixtures/vectors.json) | **Tier 2 — the contract.** Resolved output for 15 numbers. An implementation is spec-compliant exactly when it reproduces these. |
| [`AGENTS.md`](AGENTS.md) | Read-this-first for anyone (or anything) about to write an implementation. |
| [`llms.txt`](llms.txt) | Machine-readable project summary. |
| [`sigil-php/`](sigil-php) | PHP implementation — `laxit/sigil` on Packagist. |

## Implementations

| Directory | Status | Renderers |
|---|---|---|
| [`sigil-php/`](sigil-php) | working | SVG, ASCII, DXF |
| `sigil-js/` | planned | Canvas, SVG, `<sigil-glyph>` web component, font |
| `sigil-py/` | planned | server-side generation |
| `sigil-cli/` | planned | single dependency-free binary (Go/Rust) |

Each one is self-contained — its own manifest, its own dependencies, its own
test suite, its own `AGENTS.md`. Nothing is shared between them except
`fixtures/vectors.json`.

```bash
cd sigil-php && composer install && composer test
php bin/demo.php 7323 all
```

**See it in use:** [`sigil-php/examples/`](sigil-php/examples) — the whole API
in one file, identicon-style avatars, resolve-on-server/draw-in-browser, and
DXF for a laser cutter. Each is runnable, and CI runs them so they cannot rot.

A single repo does not mean a single release: implementations are tagged
independently (`php-v1.2.0`, `js-v0.3.1`), and registries that need a
manifest at the repository root are fed by a read-only `git subtree split`
mirror. See *Publishing from one repo* in [`SPEC.md`](SPEC.md).

## The one rule

One file defines the glyph. Every language reads it.

```
model.json  ──loaded by──>  each implementation's resolver  ──produces──>  glyph objects
     │                                                                          │
     └────────── regenerates ──────────> fixtures/vectors.json <── must match ───┘
```

`model.json` is the definition; `fixtures/vectors.json` is the proof each
implementation read it correctly. Changing a digit's shape is a one-line edit
in one file — not the same fix applied four times.

```bash
php sigil-php/bin/vectors.php            # regenerate the fixtures from model.json
php sigil-php/bin/vectors.php --check    # fail if the committed fixtures are stale
```

CI runs that check on every push, so an edit to `model.json` without a
regenerated `fixtures/vectors.json` fails the build rather than waiting for a
second language to disagree.

Editing `model.json` is a breaking change for every language here at once —
regenerate the fixtures in the same commit, or the implementations drift apart
silently. And never reorder its `segments` array: that order is both the bit
position of each segment in `digitMap` and the emit order, so moving an entry
silently changes what every digit means.

## Reading the glyph

Each quadrant around the stem carries one digit place:

```
 tens | ones          top-right = ones, top-left = tens,
------|------         bottom-right = hundreds, bottom-left = thousands
thou. | hund.
```

A digit turns on some subset of five candidate segments (`top`, `bottom`,
`outer`, `diagDown`, `diagUp`); `0` turns on none, `9` turns on three and
closes the box. `SPEC.md` has the full table and the reasoning behind it.

## License

MIT — see [`LICENSE`](LICENSE).
