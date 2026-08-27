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

**One repo, many languages.** The spec and the golden test vectors live at
the root; each language implementation is a subdirectory beside them. The
problem — what a glyph *is* — has one home, and every language sits next to
that definition rather than a copy of it.

| Path | What it is |
|---|---|
| [`SPEC.md`](SPEC.md) | The specification. Data model, digit→segment table, quadrant transforms, encoder algorithm, renderer contract. |
| [`fixtures/vectors.json`](fixtures/vectors.json) | **The contract.** Real encoder output for 9 numbers. An implementation is spec-compliant exactly when it reproduces these. |
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

A single repo does not mean a single release: implementations are tagged
independently (`php-v1.2.0`, `js-v0.3.1`), and registries that need a
manifest at the repository root are fed by a read-only `git subtree split`
mirror. See *Publishing from one repo* in [`SPEC.md`](SPEC.md).

## The one rule

The contract between implementations is `fixtures/vectors.json`, **not the
prose**.

```bash
php sigil-php/bin/vectors.php            # regenerate the fixtures
php sigil-php/bin/vectors.php --check    # CI: fail if the committed fixtures are stale
```

Changing that file is a breaking change for every language here at once —
regenerate it in the same commit as the change that caused it, or the
implementations drift apart silently.

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
