<?php

declare(strict_types=1);

namespace Cistercian;

use InvalidArgumentException;

/**
 * number -> digits -> resolved line segments.
 *
 * This is the whole number logic of Sigil. Renderers call segmentsFor() and
 * stem() and nothing else; they never touch SegmentModel or Quadrant.
 *
 * @phpstan-type SegmentArray array{
 *     quadrant: string, segment: string,
 *     x1: int|float, y1: int|float, x2: int|float, y2: int|float
 * }
 * @phpstan-type Line array{x1: int|float, y1: int|float, x2: int|float, y2: int|float}
 */
final class Encoder
{
    public const MIN = 0;
    public const MAX = 9999;

    /** Geometry defaults baked into sigil/fixtures/vectors.json. */
    public const DEFAULT_STEM_HEIGHT = 200;
    public const DEFAULT_QUADRANT_WIDTH = 70;
    public const DEFAULT_STEM_X = 100;
    public const DEFAULT_STEM_TOP_Y = 20;

    public function __construct(
        public readonly int|float $stemHeight = self::DEFAULT_STEM_HEIGHT,
        public readonly int|float $quadrantWidth = self::DEFAULT_QUADRANT_WIDTH,
        public readonly int|float $stemX = self::DEFAULT_STEM_X,
        public readonly int|float $stemTopY = self::DEFAULT_STEM_TOP_Y,
    ) {
    }

    /**
     * @return array{ones: int, tens: int, hundreds: int, thousands: int}
     */
    public function digitsOf(int $number): array
    {
        if ($number < self::MIN || $number > self::MAX) {
            throw new InvalidArgumentException(
                sprintf('Sigil encodes %d-%d, got %d.', self::MIN, self::MAX, $number)
            );
        }

        return [
            Quadrant::ONES      => $number % 10,
            Quadrant::TENS      => intdiv($number, 10) % 10,
            Quadrant::HUNDREDS  => intdiv($number, 100) % 10,
            Quadrant::THOUSANDS => intdiv($number, 1000) % 10,
        ];
    }

    /**
     * Active segments in global coordinates.
     *
     * Order is canonical and fixture-significant: quadrants in Quadrant::ALL
     * order, segments within a quadrant in SegmentModel::KEYS order.
     *
     * @return list<SegmentArray>
     */
    public function segmentsFor(int $number): array
    {
        $digits = $this->digitsOf($number);

        $midY = $this->stemTopY + $this->stemHeight / 2;
        $bottomY = $this->stemTopY + $this->stemHeight;

        $segments = [];

        foreach (Quadrant::ALL as $quadrant => [, $flipX, $flipY]) {
            $active = SegmentModel::DIGITS[$digits[$quadrant]];
            if ($active === []) {
                continue;
            }

            $topY = $flipY ? $midY : $this->stemTopY;
            $botY = $flipY ? $bottomY : $midY;
            $outerX = $flipX
                ? $this->stemX - $this->quadrantWidth
                : $this->stemX + $this->quadrantWidth;

            foreach (SegmentModel::KEYS as $key) {
                if (!in_array($key, $active, true)) {
                    continue;
                }

                [$lx1, $ly1, $lx2, $ly2] = SegmentModel::ENDPOINTS[$key];

                $segments[] = [
                    'quadrant' => $quadrant,
                    'segment'  => $key,
                    'x1' => $this->lerpX($lx1, $outerX),
                    'y1' => $this->lerpY($ly1, $topY, $botY),
                    'x2' => $this->lerpX($lx2, $outerX),
                    'y2' => $this->lerpY($ly2, $topY, $botY),
                ];
            }
        }

        return $segments;
    }

    /**
     * The vertical stem every glyph shares, including zero.
     *
     * @return Line
     */
    public function stem(): array
    {
        return [
            'x1' => $this->norm($this->stemX),
            'y1' => $this->norm($this->stemTopY),
            'x2' => $this->norm($this->stemX),
            'y2' => $this->norm($this->stemTopY + $this->stemHeight),
        ];
    }

    private function lerpX(int $local, int|float $outerX): int|float
    {
        return $this->norm($this->stemX + $local * ($outerX - $this->stemX));
    }

    private function lerpY(int $local, int|float $topY, int|float $botY): int|float
    {
        return $this->norm($topY + $local * ($botY - $topY));
    }

    /**
     * Collapse integral floats to int so JSON fixtures stay clean (170, not
     * 170.0) and cross-language comparison is numeric, not string-shaped.
     */
    private function norm(int|float $value): int|float
    {
        if (is_float($value) && floor($value) === $value && is_finite($value)) {
            return (int) $value;
        }

        return $value;
    }
}
