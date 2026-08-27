<p align="center">
  <img src="art/umbral_sigilstone.png" alt="Umbral Sigilstone" width="400">
</p>

# 💜 Umbral Sigilstone

**Sigil** — turns an integer into one recognizable Cistercian-style glyph.
Four digits by default, more on request.

> *The Umbral Sigilstone presses a number into a single struck mark: one stem
> and a handful of strokes, identical everywhere it is struck — in pixels, in
> ink, or in cut metal.*

[![CI](https://github.com/laxit-patel/sigil/actions/workflows/ci.yml/badge.svg)](https://github.com/laxit-patel/sigil/actions/workflows/ci.yml)

---

```text
7323  →  💜  →  svg · ascii · dxf
```

Sigil takes an integer and produces a Cistercian-style glyph — one vertical
stem plus a small set of toggled line segments, one quadrant per digit place —
in whatever output format a given delivery needs: SVG, ASCII, DXF, Canvas, a
font, audio, STL.

Four digits (`0-9999`) is the historical system and the default. It is not a
limit of the geometry: a place is a *side* of the stem and a *row* down it, so
a taller stem carries more digits and stays **one mark**. The range is derived
from how many places the model declares — up to 18, where exact integers run
out — so it can never disagree with them.

```text
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

**A spec repo, with one repo per language attached as submodules.** This repo
owns the problem — what a glyph *is* — and nothing else. Each implementation is
a real, independent repository you can clone, install and release on its own,
checked out here so the whole project reads as one thing.

| Path | What it is |
|---|---|
| [`SPEC.md`](SPEC.md) | The specification. Data model, digit→segment table, quadrant transforms, encoder algorithm, renderer contract. |
| [`model.json`](model.json) | **Tier 1 — the definition.** Segment shapes, digit map, quadrant transforms, default geometry. Every implementation *loads* this; none retype it. |
| [`fixtures/vectors.json`](fixtures/vectors.json) | **Tier 2 — the contract.** Resolved output for 15 numbers. An implementation is spec-compliant exactly when it reproduces these. |
| [`fixtures/vectors-wide.json`](fixtures/vectors-wide.json) | The same model at 1, 3 and 4 rows — what stops a port hardcoding four places. |
| [`AGENTS.md`](AGENTS.md) | Read-this-first for anyone (or anything) about to write an implementation. |
| [`llms.txt`](llms.txt) | Machine-readable project summary. |
| [`php-sigil/`](php-sigil) | PHP implementation — `laxit/sigil` on Packagist. |

## Implementations

| Repo | Package | Status | Renderers |
|---|---|---|---|
| [`php-sigil`](https://github.com/laxit-patel/php-sigil) | `laxit/sigil` | working | SVG, ASCII, DXF |
| `js-sigil` | npm | planned | Canvas, SVG, `<sigil-glyph>` web component, font |
| `py-sigil` | PyPI | planned | server-side generation |
| `cli-sigil` | binary | planned | single dependency-free binary (Go/Rust) |

Each is a standalone repo — its own manifest, dependencies, tests, CI and
`AGENTS.md` — and each vendors its own copy of `model.json` and
`fixtures/vectors.json` so it works with nothing else checked out. This repo's
CI diffs those copies against the canonical pair, so a vendored copy cannot
quietly drift.

```bash
git clone --recurse-submodules https://github.com/laxit-patel/sigil
cd sigil/php-sigil && composer install && composer test
```

**See it in use:** [`php-sigil/examples/`](php-sigil/examples) — the whole API
in one file, identicon-style avatars, resolve-on-server/draw-in-browser, and
DXF for a laser cutter. Each is runnable, and CI runs them so they cannot rot.

Each implementation publishes straight from its own repo — a manifest at the
repository root is what registries expect, so there is nothing to mirror or
split — and tags its own versions. This repo's submodule pointers record which
commit of each implementation the project was last verified against.

## The one rule

One file defines the glyph. Every language reads it.

```text
model.json  ──loaded by──>  each implementation's resolver  ──produces──>  glyph objects
     │                                                                          │
     └────────── regenerates ──────────> fixtures/vectors.json <── must match ───┘
```

`model.json` is the definition; `fixtures/vectors.json` is the proof each
implementation read it correctly. Changing a digit's shape is a one-line edit
in one file — not the same fix applied four times.

```bash
php php-sigil/bin/vectors.php            # regenerate the fixtures from model.json
php php-sigil/bin/vectors.php --check    # fail if the committed fixtures are stale
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

```text
 tens | ones          top-right = ones, top-left = tens,
------|------         bottom-right = hundreds, bottom-left = thousands
thou. | hund.
```

A digit turns on some subset of five candidate segments (`top`, `bottom`,
`outer`, `diagDown`, `diagUp`); `0` turns on none, `9` turns on three and
closes the box. `SPEC.md` has the full table and the reasoning behind it.

## License

MIT — see [`LICENSE`](LICENSE).
