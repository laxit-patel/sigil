<?php

declare(strict_types=1);

namespace Cistercian;

/**
 * The five candidate line segments of a quadrant, and which of them each
 * digit 0-9 turns on.
 *
 * Data only, no logic. This is the one table that defines what a Sigil glyph
 * looks like. Changing it is a breaking change for every language
 * implementation: regenerate sigil/fixtures/vectors.json (bin/vectors.php)
 * in the same change, or the cross-repo contract silently drifts.
 *
 * Renderers must not read this class. They consume Encoder output only.
 */
final class SegmentModel
{
    public const TOP = 'top';
    public const BOTTOM = 'bottom';
    public const OUTER = 'outer';
    public const DIAG_DOWN = 'diagDown';
    public const DIAG_UP = 'diagUp';

    /**
     * Canonical segment order. Encoder emits a quadrant's active segments in
     * this order; fixtures depend on it.
     *
     * @var list<string>
     */
    public const KEYS = [
        self::TOP,
        self::BOTTOM,
        self::OUTER,
        self::DIAG_DOWN,
        self::DIAG_UP,
    ];

    /**
     * Endpoints in the quadrant's local unit frame: [lx1, ly1, lx2, ly2].
     *
     * (0,0) is the stem/near corner of the quadrant, (1,1) the far outer
     * corner. Quadrant placement and mirroring happen in Encoder, not here.
     *
     * @var array<string, array{int, int, int, int}>
     */
    public const ENDPOINTS = [
        self::TOP       => [0, 0, 1, 0],
        self::BOTTOM    => [0, 1, 1, 1],
        self::OUTER     => [1, 0, 1, 1],
        self::DIAG_DOWN => [0, 0, 1, 1],
        self::DIAG_UP   => [0, 1, 1, 0],
    ];

    /**
     * Digit -> active segment keys.
     *
     * @var array<int, list<string>>
     */
    public const DIGITS = [
        0 => [],
        1 => [self::TOP],
        2 => [self::BOTTOM],
        3 => [self::DIAG_DOWN],
        4 => [self::DIAG_UP],
        5 => [self::TOP, self::BOTTOM],
        6 => [self::OUTER],
        7 => [self::TOP, self::OUTER],
        8 => [self::BOTTOM, self::OUTER],
        9 => [self::TOP, self::BOTTOM, self::OUTER],
    ];
}
