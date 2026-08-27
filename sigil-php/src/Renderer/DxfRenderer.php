<?php

declare(strict_types=1);

namespace Laxit\Sigil\Renderer;

use Laxit\Sigil\Encoder;

/**
 * Segment list -> minimal AutoCAD R12 DXF (LINE entities only).
 *
 * Y is negated on the way out: DXF/CAD Y grows upward, the Encoder's
 * SVG-style Y grows downward, so a straight copy would come off a laser or
 * CNC upside down.
 */
final class DxfRenderer
{
    private const EOL = "\r\n";

    public function __construct(
        private readonly Encoder $encoder,
        public readonly string $layer = '0',
    ) {
    }

    public function render(int $number): string
    {
        $out = $this->pair(0, 'SECTION')
            . $this->pair(2, 'ENTITIES');

        [$sx1, $sy1, $sx2, $sy2] = $this->encoder->stem();
        $out .= $this->lineEntity($sx1, $sy1, $sx2, $sy2);

        foreach ($this->encoder->segmentsFor($number) as $segment) {
            $out .= $this->lineEntity(
                $segment['x1'],
                $segment['y1'],
                $segment['x2'],
                $segment['y2'],
            );
        }

        return $out
            . $this->pair(0, 'ENDSEC')
            . $this->pair(0, 'EOF');
    }

    private function lineEntity(int|float $x1, int|float $y1, int|float $x2, int|float $y2): string
    {
        return $this->pair(0, 'LINE')
            . $this->pair(8, $this->layer)
            . $this->pair(10, $this->num($x1))
            . $this->pair(20, $this->num(-$y1))
            . $this->pair(30, '0.0')
            . $this->pair(11, $this->num($x2))
            . $this->pair(21, $this->num(-$y2))
            . $this->pair(31, '0.0');
    }

    private function pair(int $code, string $value): string
    {
        return $code . self::EOL . $value . self::EOL;
    }

    private function num(int|float $value): string
    {
        $formatted = rtrim(sprintf('%.6F', (float) $value), '0');

        return str_ends_with($formatted, '.') ? $formatted . '0' : $formatted;
    }
}
