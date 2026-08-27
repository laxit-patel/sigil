<?php

declare(strict_types=1);

namespace Cistercian\Renderer;

use Cistercian\Encoder;

/**
 * Segment list -> SVG document.
 *
 * One <line> per segment plus the stem, drawn slightly heavier. The viewBox
 * is derived from the returned coordinates, so this renderer stays correct
 * for any geometry parameters the Encoder was built with.
 */
final class SvgRenderer
{
    public function __construct(
        private readonly Encoder $encoder,
        public readonly string $stroke = '#111111',
        public readonly float $strokeWidth = 6.0,
        public readonly float $stemStrokeWidth = 8.0,
        public readonly float $padding = 10.0,
    ) {
    }

    public function render(int $number): string
    {
        $stem = $this->encoder->stem();
        $segments = $this->encoder->segmentsFor($number);

        [$minX, $minY, $width, $height] = $this->viewBox($stem, $segments);

        [$sx1, $sy1, $sx2, $sy2] = $stem;

        $lines = [sprintf(
            '  <line x1="%s" y1="%s" x2="%s" y2="%s" stroke-width="%s"/>',
            $this->num($sx1),
            $this->num($sy1),
            $this->num($sx2),
            $this->num($sy2),
            $this->num($this->stemStrokeWidth),
        )];

        foreach ($segments as $segment) {
            $lines[] = sprintf(
                '  <line x1="%s" y1="%s" x2="%s" y2="%s"/><!-- %s.%s -->',
                $this->num($segment['x1']),
                $this->num($segment['y1']),
                $this->num($segment['x2']),
                $this->num($segment['y2']),
                $segment['quadrant'],
                $segment['segment'],
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s %s %s %s" '
            . 'role="img" aria-label="Sigil glyph for %d">' . "\n"
            . '  <g stroke="%s" stroke-width="%s" stroke-linecap="round" fill="none">' . "\n"
            . '%s' . "\n"
            . '  </g>' . "\n"
            . '</svg>',
            $this->num($minX),
            $this->num($minY),
            $this->num($width),
            $this->num($height),
            $number,
            $this->stroke,
            $this->num($this->strokeWidth),
            implode("\n", $lines),
        );
    }

    /**
     * @param array{int|float, int|float, int|float, int|float} $stem
     * @param list<array{quadrant: string, segment: string, x1: int|float, y1: int|float, x2: int|float, y2: int|float}> $segments
     * @return array{float, float, float, float}
     */
    private function viewBox(array $stem, array $segments): array
    {
        [$sx1, $sy1, $sx2, $sy2] = $stem;

        $xs = [$sx1, $sx2];
        $ys = [$sy1, $sy2];

        foreach ($segments as $segment) {
            $xs[] = $segment['x1'];
            $xs[] = $segment['x2'];
            $ys[] = $segment['y1'];
            $ys[] = $segment['y2'];
        }

        $pad = $this->padding + max($this->strokeWidth, $this->stemStrokeWidth) / 2;

        $minX = min($xs) - $pad;
        $minY = min($ys) - $pad;

        return [$minX, $minY, max($xs) + $pad - $minX, max($ys) + $pad - $minY];
    }

    private function num(int|float $value): string
    {
        if (is_int($value) || (float) (int) $value === (float) $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(sprintf('%.4F', $value), '0'), '.');
    }
}
