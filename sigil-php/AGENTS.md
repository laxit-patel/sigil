# AGENTS.md

The PHP implementation of Sigil. **The spec and the contract live at the repo
root, one level up** — read `../SPEC.md` and `../AGENTS.md` before changing
anything under `src/`.

## The rule that outranks everything else

`../fixtures/vectors.json` is the contract shared by every language in this
repo. This package is correct exactly when `tests/VectorsTest.php` passes
against it.

If you change `SegmentModel::DIGITS`, `Quadrant::ALL`, the segment endpoints,
the canonical ordering, or the default geometry, you have changed the contract
for **every** implementation here — PHP, JS, Python, everything planned. Then:

1. `php bin/vectors.php` — regenerate `../fixtures/vectors.json`.
2. `composer test` — confirm the suite still passes.
3. Commit the fixtures **in the same commit**, and say in the message that it
   is a breaking change for the other implementations.

Never hand-edit the fixtures, and never adjust a test to match new output when
the fixture says otherwise — that is the failure mode this whole setup exists
to prevent.

## Architecture constraints

- `SegmentModel` and `Quadrant` are **data only**. No logic, no methods.
- `Encoder` is the only place number logic lives.
- **Renderers use `Encoder::segmentsFor()` and `Encoder::stem()` and nothing
  else.** Importing `SegmentModel` or `Quadrant` from `src/Renderer/` fails
  `RendererTest::testRenderersDoNotReachIntoTheNumberLogic`. If a renderer
  seems to need the tables, it needs the *labels* on the segments Encoder
  already returns — see how `AsciiRenderer` does it.
- Zero runtime dependencies. Keep it that way; ports are supposed to be a
  ~150-line read.
- This directory is self-contained. Don't reach sideways into a sibling
  implementation, and don't build tooling shared with one — the only thing
  crossing the boundary is `../fixtures/vectors.json`.

## Adding a renderer

New file in `src/Renderer/`, constructor takes an `Encoder`, one `render(int
$number): string` method, plus a smoke test in `tests/RendererTest.php`.
Renderers get smoke tests, not golden files — only the encoder's segment list
is the contract. Add a row to the renderer table in `README.md`.

## Commands

```bash
cd sigil-php                   # every command below runs from this directory
composer install
composer test                  # phpunit, including the fixture compliance suite
php bin/demo.php 7323 all      # render one glyph in every format
php bin/vectors.php            # regenerate ../fixtures/vectors.json
php bin/vectors.php --check    # CI: fail if the committed fixtures are stale
```

## Conventions

- `declare(strict_types=1)` in every file.
- Typed properties, readonly promoted constructor params, `final` classes.
- Comments explain *why*, not what. The digit table and the ordering rules
  carry the load-bearing comments; don't strip them.
