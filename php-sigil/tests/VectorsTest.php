<?php

declare(strict_types=1);

namespace Laxit\Sigil\Tests;

use Laxit\Sigil\Encoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The compliance check.
 *
 * This is the only test that decides whether php-sigil is "spec-compliant":
 * it replays fixtures/vectors.json from the repo root and asserts this
 * implementation reproduces every vector exactly. Every language
 * implementation in this repo has the equivalent of this file, running
 * against the same JSON.
 */
final class VectorsTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function vectorProvider(): array
    {
        $fixtures = self::load();
        $cases = [];

        foreach ($fixtures['vectors'] as $vector) {
            $cases['number ' . $vector['number']] = [$vector];
        }

        return $cases;
    }

    public function testEncoderUsesTheGeometryTheFixturesWereGeneratedWith(): void
    {
        $encoder = new Encoder();

        // Not redundant with the coordinate assertions below: if model.json's
        // geometryDefaults are edited without regenerating the fixtures, this
        // names the cause instead of leaving 15 coordinate failures to read.
        self::assertSame(200, $encoder->stemHeight);
        self::assertSame(70, $encoder->quadrantWidth);
        self::assertSame(100, $encoder->stemX);
        self::assertSame(20, $encoder->stemTopY);
    }

    /**
     * @param array<string, mixed> $vector
     */
    #[DataProvider('vectorProvider')]
    public function testDigitsMatchTheFixture(array $vector): void
    {
        self::assertSame(
            $vector['digits'],
            (new Encoder())->digitsOf($vector['number']),
        );
    }

    /**
     * @param array<string, mixed> $vector
     */
    #[DataProvider('vectorProvider')]
    public function testStemMatchesTheFixture(array $vector): void
    {
        self::assertSame(
            $vector['stem'],
            (new Encoder())->stem(),
        );
    }

    /**
     * @param array<string, mixed> $vector
     */
    #[DataProvider('vectorProvider')]
    public function testSegmentsMatchTheFixture(array $vector): void
    {
        self::assertSame(
            $vector['segments'],
            (new Encoder())->segmentsFor($vector['number']),
            sprintf(
                'segmentsFor(%d) drifted from fixtures/vectors.json. If the digit map '
                . 'changed on purpose, regenerate the fixtures with bin/vectors.php '
                . 'and commit them -- that is a breaking change for every language '
                . 'implementation in this repo.',
                $vector['number'],
            ),
        );
    }

    /**
     * @return array{vectors: list<array<string, mixed>>}
     */
    private static function load(): array
    {
        $candidates = array_filter([
            getenv('SIGIL_FIXTURES') ?: null,
            __DIR__ . '/../fixtures/vectors.json',      // this package
            __DIR__ . '/../../fixtures/vectors.json',   // the spec repo, when checked out around us
        ]);

        $path = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if ($path === null) {
            $path = end($candidates);
        }

        if (!is_file($path)) {
            self::fail(
                "Cannot find the golden vectors at {$path}. They live at "
                . 'fixtures/vectors.json in the repo root; set SIGIL_FIXTURES to '
                . 'point somewhere else.'
            );
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
