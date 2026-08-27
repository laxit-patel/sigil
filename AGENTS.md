# AGENTS.md

This is the **Sigil spec repo**. It owns the definition and nothing else.
Each language implementation is its own independent repository, checked out
here as a submodule (`php-sigil/`, later `js-sigil/`, `py-sigil/`,
`cli-sigil/`).

Clone with `--recurse-submodules`, or the implementation directories are
empty. A change inside one of them is committed and pushed **in that repo**,
then the submodule pointer is updated here.

Before writing code in any implementation directory:

1. **Read `SPEC.md` in full.** It explains *why* the fixtures say what they
   say; skimming it produces code that passes nothing.
2. **Read that directory's own `AGENTS.md`** for what is specific to the
   language — layout, commands, conventions.
3. **Load `model.json`. Do not retype it.** It is Tier 1 — the declarative
   definition of segment shapes, digit map, quadrant transforms and default
   geometry. Your implementation reads that file; it does not hold its own
   copy of the tables. Hardcoding them is how two languages drift apart.
4. **Your implementation must reproduce every vector in
   `fixtures/vectors.json` exactly** — same segments, same coordinates, same
   order. That file, not this prose, is what CI checks. An implementation is
   "spec-compliant" exactly when it replays the fixtures and matches.
5. **If you change `model.json`, regenerate `fixtures/vectors.json` in the
   same commit** with `php php-sigil/bin/vectors.php`. That is a breaking
   change for every language here at once, not just the one you are editing.
6. **Never reorder the `segments` array in `model.json`.** Its order is both
   the bit position of each segment in `digitMap` and the emit order — moving
   an entry silently changes what every digit means. Append only.
7. **Renderers call `Encoder.segmentsFor()` and `Encoder.stem()` and nothing
   else.** They must not import or reference `SegmentModel` / `Quadrant`. That
   single constraint is what keeps new output formats and new languages cheap.

## Layout

```
SPEC.md               the specification
model.json            Tier 1 -- canonical definition
fixtures/vectors.json Tier 2 -- canonical resolved output
php-sigil/            submodule -> github.com/laxit-patel/php-sigil
```

`model.json` and `fixtures/vectors.json` here are **canonical**. Every
implementation vendors its own copy of both at its repo root, so it installs
and tests standalone — a package from Packagist has no spec repo above it.
Copies drift, so this repo's CI diffs each submodule's pair against the
canonical pair on every push and on a schedule.

Changing the definition is therefore a two-repo operation:

1. Edit `model.json` here.
2. In each implementation: copy both canonical files in, regenerate fixtures,
   run the suite, commit, push.
3. Update the submodule pointers here and commit.

An implementation directory is self-contained: its own manifest, its own
dependencies, its own tests. Nothing is shared across them except
`fixtures/vectors.json`. Don't invent shared tooling between languages —
duplication across four small implementations is cheaper than a build system
they all have to agree on.

## Adding a language

1. Create a `<lang>-sigil` repo with that ecosystem's normal layout, then
   `git submodule add` it here.
2. Copy `model.json` and `fixtures/vectors.json` into it.
3. Write the generic resolver: load `model.json`, implement `digitsOf` and
   `segmentsFor` over whatever it declares. Pure arithmetic, no tables, no
   dependencies. Renderers are rewritten idiomatically, not translated.
4. Write the compliance test **first**: load `fixtures/vectors.json` and
   assert your `segmentsFor` matches every vector. Model it on
   `php-sigil/tests/VectorsTest.php`.
5. Write its `AGENTS.md` and `README.md`.
6. Add it to the table in the root `README.md` and to `llms.txt`.

Checklist for the port itself:

- [ ] `digitsOf` validates `0 <= n <= 9999` and throws/raises otherwise.
- [ ] `segmentsFor` emits quadrants in `model.json`'s `places` order and
      segments in its `segments` order — read from the file, not assumed.
- [ ] `digitMap` is decoded as a bitmask (bit *i* = the *i*-th entry of
      `segments`), not as named arrays.
- [ ] Coordinates compare **numerically** against the fixtures, not as strings.
- [ ] The test suite loads `fixtures/vectors.json` itself rather than
      hand-copying values out of it.

## What not to do

- Don't hand-edit `fixtures/vectors.json` — it is generated from `model.json`.
- Don't copy `model.json`'s tables into source. Load the file.
- Don't adjust a test to match new output when the fixture says otherwise —
  that is the exact failure mode this repo is built to prevent.
- Don't put implementation code in this repo; it belongs in a language repo.
- Don't edit a submodule's files and commit only here — the change lives in
  that repo, and this repo records only which commit of it is current.
- Don't "fix" the coincident midpoint lines or the always-drawn stem — see
  *Known properties of this model* in `SPEC.md`; both are intentional.
