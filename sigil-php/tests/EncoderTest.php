<?php

declare(strict_types=1);

namespace Cistercian\Tests;

use Cistercian\Encoder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Behaviour the golden vectors cannot cover: the boundaries, the failure
 * mode, and non-default geometry.
 */
final class EncoderTest extends TestCase
{
    /**
     * @return list<array{int}>
     */
    public static function outOfRangeProvider(): array
    {
        return [[-1], [10000], [PHP_INT_MAX]];
    }

    #[DataProvider('outOfRangeProvider')]
    public function testRejectsNumbersOutsideZeroToNineThousandNineNineNine(int $number): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Encoder())->segmentsFor($number);
    }

    public function testAcceptsBothEnds(): void
    {
        $encoder = new Encoder();

        self::assertSame([], $encoder->segmentsFor(0), 'zero is the stem alone');
        self::assertCount(12, $encoder->segmentsFor(9999), '9999 is four closed boxes');
    }

    public function testDigitsSplitByPlaceValue(): void
    {
        self::assertSame(
            ['ones' => 3, 'tens' => 2, 'hundreds' => 3, 'thousands' => 7],
            (new Encoder())->digitsOf(7323),
        );
    }

    public function testGeometryIsConfigurable(): void
    {
        $encoder = new Encoder(stemHeight: 100, quadrantWidth: 35, stemX: 50, stemTopY: 10);

        self::assertSame([50, 10, 50, 110], $encoder->stem());

        // ones.top: stem corner across to the outer edge, on the top row.
        self::assertSame(
            [['quadrant' => 'ones', 'segment' => 'top', 'x1' => 50, 'y1' => 10, 'x2' => 85, 'y2' => 10]],
            $encoder->segmentsFor(1),
        );
    }

    /**
     * The point of the IR: the digit map is data, not code. If this passes
     * with an edited model and unchanged output, the tables have been
     * hardcoded again somewhere and the IR is decorative.
     */
    public function testTheDigitMapIsReadFromModelJsonNotHardcoded(): void
    {
        $model = json_decode((string) file_get_contents(Encoder::locateModel()), true);

        // Remap digit 1 from `top` (bit 0) to `outer` (bit 2).
        $model['digitMap'][1] = 1 << 2;

        $path = tempnam(sys_get_temp_dir(), 'sigil-model-') . '.json';
        file_put_contents($path, json_encode($model, JSON_THROW_ON_ERROR));

        try {
            $segments = (new Encoder($path))->segmentsFor(1);

            self::assertCount(1, $segments);
            self::assertSame('outer', $segments[0]['segment']);
            self::assertSame('top', (new Encoder())->segmentsFor(1)[0]['segment']);
        } finally {
            unlink($path);
        }
    }

    public function testSegmentOrderFollowsModelJsonDeclarationOrder(): void
    {
        $encoder = new Encoder();

        // The `segments` array order in model.json is both the bit position
        // in digitMap and the emit order. Nothing else may define it.
        self::assertSame(
            ['top', 'bottom', 'outer', 'diagDown', 'diagUp'],
            $encoder->segmentModel->keys,
        );
        self::assertSame(
            ['ones', 'tens', 'hundreds', 'thousands'],
            $encoder->quadrant->names,
        );
    }

    public function testMissingModelFails(): void
    {
        $this->expectException(RuntimeException::class);

        new Encoder('/nonexistent/model.json');
    }

    public function testSegmentOrderIsCanonical(): void
    {
        // Quadrants ones -> thousands, and within a quadrant the SegmentModel
        // key order. Renderers and fixtures both rely on this being stable.
        $labels = array_map(
            static fn (array $s): string => $s['quadrant'] . '.' . $s['segment'],
            (new Encoder())->segmentsFor(9999),
        );

        self::assertSame([
            'ones.top', 'ones.bottom', 'ones.outer',
            'tens.top', 'tens.bottom', 'tens.outer',
            'hundreds.top', 'hundreds.bottom', 'hundreds.outer',
            'thousands.top', 'thousands.bottom', 'thousands.outer',
        ], $labels);
    }
}
