# Sigil

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

**This repo is spec only.** It holds no implementation code — just the
specification, the golden test vectors every implementation is validated
against, and the pointers that keep AI agents and human porters honest.

| File | What it is |
|---|---|
| [`SPEC.md`](SPEC.md) | The specification. Data model, digit→segment table, quadrant transforms, encoder algorithm, renderer contract. |
| [`fixtures/vectors.json`](fixtures/vectors.json) | **The contract.** Real encoder output for 9 numbers. An implementation is spec-compliant exactly when it reproduces these. |
| [`AGENTS.md`](AGENTS.md) | Read-this-first for anyone (or anything) about to write code in a `sigil-*` repo. |
| [`llms.txt`](llms.txt) | Machine-readable project summary. |

## Implementations

| Repo | Status | Renderers |
|---|---|---|
| [`sigil-php`](../sigil-php) — `laxit/sigil` on Packagist | first target | SVG, ASCII, DXF |
| `sigil-js` | planned | Canvas, SVG, `<sigil-glyph>` web component, font |
| `sigil-py` | planned | server-side generation |
| `sigil-cli` | planned | single dependency-free binary (Go/Rust) |

One idea across several repos rather than a monorepo, so each language can be
versioned and released independently.

## The one rule

The contract between repos is `fixtures/vectors.json`, **not the prose**.

```bash
# in sigil-php, after any change to the digit map or default geometry:
php bin/vectors.php            # regenerate the fixtures in this repo
php bin/vectors.php --check    # CI: fail if the committed fixtures are stale
```

Changing that file is a breaking change for every language port at once.
Regenerate it and commit both repos together, or ports drift apart silently.

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
