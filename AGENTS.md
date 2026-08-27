# AGENTS.md

This repo is the **spec-only root** of the Sigil project. There is no
implementation code here on purpose — implementations live in sibling repos
(`sigil-php`, later `sigil-js`, `sigil-py`, `sigil-cli`).

Before implementing anything in a `sigil-*` repo:

1. **Read `SPEC.md` in full.** It explains *why* the fixtures say what they
   say; skimming it produces code that passes nothing.
2. **Your implementation must reproduce every vector in
   `fixtures/vectors.json` exactly** — same digit→segment mapping, same
   segments, same coordinates, same order. That file, not this prose, is what
   CI checks. An implementation is "spec-compliant" exactly when it replays
   the fixtures and matches.
3. **Do not modify `SegmentModel` / `Quadrant` / `DIGIT_MAP`-equivalent logic
   without regenerating `fixtures/vectors.json` here first.** It is the
   cross-repo contract; changing it is a breaking change for every language
   implementation, all at once. Regenerate with `sigil-php/bin/vectors.php`
   and commit the root repo and the implementation repo together.
4. **Renderers call `Encoder.segmentsFor()` and `Encoder.stem()` and nothing
   else.** They must not import or reference `SegmentModel` / `Quadrant`. That
   single constraint is what keeps new output formats and new language ports
   cheap.

## Porting checklist

Only `SegmentModel`, `Quadrant` and `Encoder` need porting — under ~150 lines,
zero dependencies. Renderers are rewritten idiomatically per language, not
translated line by line.

- [ ] `digitsOf` validates `0 <= n <= 9999` and throws/raises otherwise.
- [ ] `segmentsFor` emits quadrants in `ones, tens, hundreds, thousands` order
      and segments in `top, bottom, outer, diagDown, diagUp` order.
- [ ] Coordinates compare **numerically** against the fixtures, not as strings.
- [ ] The test suite loads `fixtures/vectors.json` itself rather than
      hand-copying values out of it.
- [ ] `README` states which renderers that port ships.

## What not to do here

- Don't add implementation code to this repo.
- Don't hand-edit `fixtures/vectors.json`.
- Don't "fix" the coincident midpoint lines or the always-drawn stem — see
  *Known properties of this model* in `SPEC.md`; both are intentional.
