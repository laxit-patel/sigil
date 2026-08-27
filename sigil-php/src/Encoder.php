<?php

declare(strict_types=1);

namespace Laxit\Sigil;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * The generic resolver: model.json (Tier 1) -> glyph objects (Tier 2).
 *
 * This class knows no Cistercian-specific tables. It is pure arithmetic and
 * interpolation over whatever model.json declares, which is why it stays
 * small and why a wrong digit map cannot hide in it -- the data lives in one
 * JSON file every language implementation reads, not retyped per port.
 *
 * Renderers call segmentsFor() and stem() and nothing else; they never touch
 * SegmentModel or Quadrant.
 *
 * @phpstan-type SegmentArray array{
 *     quadrant: string, segment: string,
 *     x1: int|float, y1: int|float, x2: int|float, y2: int|float
 * }
 */
final class Encoder
{
    public readonly SegmentModel $segmentModel;
    public readonly Quadrant $quadrant;

    public readonly int $min;
    public readonly int $max;

    public readonly int|float $stemHeight;
    public readonly int|float $quadrantWidth;
    public readonly int|float $stemX;
    public readonly int|float $stemTopY;

    /**
     * Geometry arguments override model.json's `geometryDefaults`; leave them
     * null to use the defaults the fixtures were generated with.
     */
    public function __construct(
        ?string $modelPath = null,
        int|float|null $stemHeight = null,
        int|float|null $quadrantWidth = null,
        int|float|null $stemX = null,
        int|float|null $stemTopY = null,
    ) {
        $model = self::loadModel($modelPath ?? self::locateModel());

        $this->segmentModel = new SegmentModel($model);
        $this->quadrant = new Quadrant($model);

        $this->min = $model['range']['min'];
        $this->max = $model['range']['max'];

        $g = $model['geometryDefaults'];
        $this->stemHeight = $stemHeight ?? $g['stemHeight'];
        $this->quadrantWidth = $quadrantWidth ?? $g['quadrantWidth'];
        $this->stemX = $stemX ?? $g['stemX'];
        $this->stemTopY = $stemTopY ?? $g['stemTopY'];
    }

    /**
     * @return array<string, int>
     */
    public function digitsOf(int $number): array
    {
        if ($number < $this->min || $number > $this->max) {
            throw new InvalidArgumentException(
                sprintf('Sigil encodes %d-%d, got %d.', $this->min, $this->max, $number)
            );
        }

        $digits = [];
        $divisor = 1;

        foreach ($this->quadrant->names as $place) {
            $digits[$place] = intdiv($number, $divisor) % 10;
            $divisor *= 10;
        }

        return $digits;
    }

    /**
     * Active segments in global coordinates.
     *
     * Order is canonical and fixture-significant: quadrants in model.json's
     * `places` order, segments within a quadrant in `segments` order.
     *
     * @return list<SegmentArray>
     */
    public function segmentsFor(int $number): array
    {
        $digits = $this->digitsOf($number);

        $midY = $this->stemTopY + $this->stemHeight / 2;
        $bottomY = $this->stemTopY + $this->stemHeight;

        $segments = [];

        foreach ($this->quadrant->names as $place) {
            $active = $this->segmentModel->segmentsForDigit($digits[$place]);
            if ($active === []) {
                continue;
            }

            ['flipX' => $flipX, 'flipY' => $flipY] = $this->quadrant->placement($place);

            $topY = $flipY ? $midY : $this->stemTopY;
            $botY = $flipY ? $bottomY : $midY;
            $outerX = $flipX
                ? $this->stemX - $this->quadrantWidth
                : $this->stemX + $this->quadrantWidth;

            foreach ($active as $key) {
                [$lx1, $ly1, $lx2, $ly2] = $this->segmentModel->endpoints[$key];

                $segments[] = [
                    'quadrant' => $place,
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
     * The vertical stem every glyph shares, including zero, as [x1,y1,x2,y2].
     *
     * @return array{int|float, int|float, int|float, int|float}
     */
    public function stem(): array
    {
        return [
            $this->norm($this->stemX),
            $this->norm($this->stemTopY),
            $this->norm($this->stemX),
            $this->norm($this->stemTopY + $this->stemHeight),
        ];
    }

    /**
     * Where model.json is, in order of preference: an explicit path, the
     * SIGIL_MODEL environment variable, a copy shipped inside this package
     * (how the Packagist split mirror gets one), then the repo root.
     */
    public static function locateModel(): string
    {
        $candidates = array_filter([
            getenv('SIGIL_MODEL') ?: null,
            __DIR__ . '/../model.json',
            __DIR__ . '/../../model.json',
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException(
            'Cannot find model.json. It lives at the repo root; set SIGIL_MODEL to '
            . 'point somewhere else.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadModel(string $path): array
    {
        $raw = @file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException("Cannot read model.json at {$path}.");
        }

        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("model.json at {$path} is not valid JSON: {$e->getMessage()}", 0, $e);
        }
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
