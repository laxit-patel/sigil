<?php

declare(strict_types=1);

namespace Cistercian;

/**
 * The four placements of the segment model around the stem.
 *
 * Data only, no logic. Order is canonical: Encoder walks quadrants in this
 * order and fixtures depend on it.
 *
 * Renderers must not read this class. They consume Encoder output only.
 */
final class Quadrant
{
    public const ONES = 'ones';
    public const TENS = 'tens';
    public const HUNDREDS = 'hundreds';
    public const THOUSANDS = 'thousands';

    /**
     * name => [place value, flipX (left of stem), flipY (below midpoint)]
     *
     * @var array<string, array{int, bool, bool}>
     */
    public const ALL = [
        self::ONES      => [1,    false, false],
        self::TENS      => [10,   true,  false],
        self::HUNDREDS  => [100,  false, true],
        self::THOUSANDS => [1000, true,  true],
    ];
}
