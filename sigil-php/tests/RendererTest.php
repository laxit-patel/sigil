<?php

declare(strict_types=1);

namespace Cistercian\Tests;

use Cistercian\Encoder;
use Cistercian\Renderer\AsciiRenderer;
use Cistercian\Renderer\DxfRenderer;
use Cistercian\Renderer\SvgRenderer;
use PHPUnit\Framework\TestCase;

/**
 * Renderers get smoke tests, not golden files: their output is format detail,
 * and only the Encoder's segment list is the cross-repo contract.
 *
 * The one structural rule that IS enforced here is the model/renderer
 * separation -- see testRenderersDoNotReachIntoTheNumberLogic.
 */
final class RendererTest extends TestCase
{
    private Encoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new Encoder();
    }

    public function testSvgIsWellFormedAndDrawsEverySegmentPlusTheStem(): void
    {
        $svg = (new SvgRenderer($this->encoder))->render(9999);

        self::assertNotFalse(simplexml_load_string($svg), 'SVG must parse as XML');
        self::assertSame(13, substr_count($svg, '<line '), '12 segments + the stem');
        self::assertStringContainsString('viewBox="16 6 168 228"', $svg);
    }

    public function testSvgForZeroIsTheStemAlone(): void
    {
        $svg = (new SvgRenderer($this->encoder))->render(0);

        self::assertSame(1, substr_count($svg, '<line '));
    }

    public function testAsciiGridIsSquareAndCentredOnTheStem(): void
    {
        $rows = explode("\n", (new AsciiRenderer($this->encoder))->render(9999));

        self::assertCount(9, $rows, '2 * 4 cells + 1');

        foreach ($rows as $row) {
            self::assertSame(9, strlen($row));
            self::assertSame('|', $row[4], 'the stem holds the centre column');
        }
    }

    public function testAsciiDrawsBothDiagonals(): void
    {
        $renderer = new AsciiRenderer($this->encoder);

        self::assertStringContainsString('\\', $renderer->render(3));
        self::assertStringContainsString('/', $renderer->render(4));
    }

    public function testDxfIsAnR12EntitiesSectionWithYFlipped(): void
    {
        $dxf = (new DxfRenderer($this->encoder))->render(1);

        self::assertStringStartsWith("0\r\nSECTION\r\n2\r\nENTITIES\r\n", $dxf);
        self::assertStringEndsWith("0\r\nENDSEC\r\n0\r\nEOF\r\n", $dxf);
        self::assertSame(2, substr_count($dxf, "\r\nLINE\r\n"), 'ones.top + the stem');

        // The stem runs y 20..220 in encoder space; CAD Y grows the other way.
        self::assertStringContainsString("20\r\n-20.0\r\n", $dxf);
        self::assertStringContainsString("21\r\n-220.0\r\n", $dxf);
        self::assertStringNotContainsString("\r\n220.0\r\n", $dxf);
    }

    /**
     * The architectural constraint from the spec: renderers consume
     * Encoder::segmentsFor()/stem() and nothing else. Reaching into
     * SegmentModel or Quadrant is what makes new formats and new language
     * ports expensive, so it fails the build rather than review.
     */
    public function testRenderersDoNotReachIntoTheNumberLogic(): void
    {
        foreach (glob(__DIR__ . '/../src/Renderer/*.php') ?: [] as $file) {
            $code = (string) file_get_contents($file);

            self::assertDoesNotMatchRegularExpression(
                '/\b(SegmentModel|Quadrant)\b\s*(::|\$)|use\s+Cistercian\\\\(SegmentModel|Quadrant)\s*;/',
                $code,
                basename($file) . ' must not use SegmentModel or Quadrant directly.',
            );
        }
    }
}
