<?php

declare(strict_types=1);

namespace Cistercian\Renderer;

use Cistercian\Encoder;

/**
 * Segment list -> monospace text glyph.
 *
 * Works on its own small integer grid rather than reusing the continuous
 * SVG-scale coordinates: at 4 cells per quadrant a 200-unit stem would be
 * meaningless. It reads only the `quadrant` and `segment` labels the Encoder
 * puts on each returned segment and looks them up in its own grid tables --
 * it never imports SegmentModel or Quadrant.
 */
final class AsciiRenderer
{
    private const HORIZONTAL = '-';
    private const VERTICAL = '|';
    private const DIAG_DOWN = '\\';
    private const DIAG_UP = '/';

    /** Segment label -> endpoints in the quadrant's local unit frame. */
    private const LOCAL = [
        'top'       => [0, 0, 1, 0],
        'bottom'    => [0, 1, 1, 1],
        'outer'     => [1, 0, 1, 1],
        'diagDown'  => [0, 0, 1, 1],
        'diagUp'    => [0, 1, 1, 0],
    ];

    /** Segment label -> the character it draws with. */
    private const GLYPH = [
        'top'      => self::HORIZONTAL,
        'bottom'   => self::HORIZONTAL,
        'outer'    => self::VERTICAL,
        'diagDown' => self::DIAG_DOWN,
        'diagUp'   => self::DIAG_UP,
    ];

    /** Quadrant label -> [flipX (left of stem), flipY (below midpoint)]. */
    private const PLACEMENT = [
        'ones'      => [false, false],
        'tens'      => [true,  false],
        'hundreds'  => [false, true],
        'thousands' => [true,  true],
    ];

    /**
     * @param positive-int $cells Grid cells per quadrant, in each direction.
     */
    public function __construct(
        private readonly Encoder $encoder,
        public readonly int $cells = 4,
        public readonly string $blank = ' ',
    ) {
    }

    public function render(int $number): string
    {
        $q = $this->cells;
        $size = 2 * $q + 1;

        /** @var list<list<string>> $grid */
        $grid = array_fill(0, $size, array_fill(0, $size, $this->blank));

        foreach ($this->encoder->segmentsFor($number) as $segment) {
            [$flipX, $flipY] = self::PLACEMENT[$segment['quadrant']];
            [$lx1, $ly1, $lx2, $ly2] = self::LOCAL[$segment['segment']];

            $stemCol = $q;
            $outerCol = $flipX ? 0 : 2 * $q;
            $topRow = $flipY ? $q : 0;
            $botRow = $flipY ? 2 * $q : $q;

            $this->line(
                $grid,
                $stemCol + $lx1 * ($outerCol - $stemCol),
                $topRow + $ly1 * ($botRow - $topRow),
                $stemCol + $lx2 * ($outerCol - $stemCol),
                $topRow + $ly2 * ($botRow - $topRow),
                self::GLYPH[$segment['segment']],
            );
        }

        // Stem last so it stays unbroken where horizontals meet it.
        $this->line($grid, $q, 0, $q, 2 * $q, self::VERTICAL);

        return implode("\n", array_map(
            static fn (array $row): string => rtrim(implode('', $row)),
            $grid,
        ));
    }

    /**
     * Bresenham-style integer stepping. Handles the diagonals; horizontals
     * and verticals fall out of the same loop.
     *
     * @param list<list<string>> $grid
     */
    private function line(array &$grid, int $col1, int $row1, int $col2, int $row2, string $char): void
    {
        $dCol = abs($col2 - $col1);
        $dRow = -abs($row2 - $row1);
        $stepCol = $col1 < $col2 ? 1 : -1;
        $stepRow = $row1 < $row2 ? 1 : -1;
        $error = $dCol + $dRow;

        $col = $col1;
        $row = $row1;

        while (true) {
            $grid[$row][$col] = $char;

            if ($col === $col2 && $row === $row2) {
                return;
            }

            $doubled = 2 * $error;

            if ($doubled >= $dRow) {
                $error += $dRow;
                $col += $stepCol;
            }

            if ($doubled <= $dCol) {
                $error += $dCol;
                $row += $stepRow;
            }
        }
    }
}
