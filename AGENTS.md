# AGENTS.md

This is the **root of the Sigil monorepo**. The spec and the golden fixtures
live here; each language implementation is a subdirectory beside them
(`sigil-php/`, later `sigil-js/`, `sigil-py/`, `sigil-cli/`).

Before writing code in any implementation directory:

1. **Read `SPEC.md` in full.** It explains *why* the fixtures say what they
   say; skimming it produces code that passes nothing.
2. **Read that directory's own `AGENTS.md`** for what is specific to the
   language — layout, commands, conventions.
3. **Your implementation must reproduce every vector in
   `fixtures/vectors.json` exactly** — same digit→segment mapping, same
   segments, same coordinates, same order. That file, not this prose, is what
   CI checks. An implementation is "spec-compliant" exactly when it replays
   the fixtures and matches.
4. **Do not modify `SegmentModel` / `Quadrant` / `DIGIT_MAP`-equivalent logic
   without regenerating `fixtures/vectors.json` in the same commit.** It is
   the contract between every language here; changing it breaks all of them
   at once, not just the one you are editing. Regenerate with
   `php sigil-php/bin/vectors.php`.
5. **Renderers call `Encoder.segmentsFor()` and `Encoder.stem()` and nothing
   else.** They must not import or reference `SegmentModel` / `Quadrant`. That
   single constraint is what keeps new output formats and new languages cheap.

## Layout

```
SPEC.md               the specification
fixtures/vectors.json the contract -- generated, never hand-edited
sigil-php/            PHP implementation (laxit/sigil)
```

An implementation directory is self-contained: its own manifest, its own
dependencies, its own tests. Nothing is shared across them except
`fixtures/vectors.json`. Don't invent shared tooling between languages —
duplication across four small implementations is cheaper than a build system
they all have to agree on.

## Adding a language

1. `mkdir sigil-<lang>/` with that ecosystem's normal layout.
2. Port `SegmentModel`, `Quadrant`, `Encoder` — under ~150 lines, zero
   dependencies. Renderers are rewritten idiomatically, not translated.
3. Write the compliance test **first**: load `../fixtures/vectors.json` and
   assert your `segmentsFor` matches every vector. Model it on
   `sigil-php/tests/VectorsTest.php`.
4. Write `sigil-<lang>/AGENTS.md` and `sigil-<lang>/README.md`.
5. Add the directory to the table in the root `README.md` and to `llms.txt`.

Checklist for the port itself:

- [ ] `digitsOf` validates `0 <= n <= 9999` and throws/raises otherwise.
- [ ] `segmentsFor` emits quadrants in `ones, tens, hundreds, thousands` order
      and segments in `top, bottom, outer, diagDown, diagUp` order.
- [ ] Coordinates compare **numerically** against the fixtures, not as strings.
- [ ] The test suite loads `fixtures/vectors.json` itself rather than
      hand-copying values out of it.

## What not to do

- Don't hand-edit `fixtures/vectors.json`.
- Don't adjust a test to match new output when the fixture says otherwise —
  that is the exact failure mode this repo is built to prevent.
- Don't put implementation code at the root; it belongs in a language
  directory.
- Don't "fix" the coincident midpoint lines or the always-drawn stem — see
  *Known properties of this model* in `SPEC.md`; both are intentional.
