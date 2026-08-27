<?php

declare(strict_types=1);

namespace Laxit\Sigil;

use InvalidArgumentException;

/**
 * Typed wrapper around the `segments` and `digitMap` halves of model.json.
 *
 * It holds no table of its own. The five candidate segments and the digit map
 * live in model.json at the repo root, so changing a digit's shape is a
 * one-line edit in one file that every language implementation picks up --
 * rather than the same fix applied N times across N ports.
 *
 * Renderers must not use this class. They consume Encoder output only.
 */
final class SegmentModel
{
    /**
     * Segment keys in declaration order.
     *
     * This order is doubly load-bearing: it is both the bit position of each
     * segment in `digitMap` and the order Encoder emits them in. Reordering
     * the `segments` array in model.json silently changes what every digit
     * means -- it is not a cosmetic edit.
     *
     * @var list<string>
     */
    public readonly array $keys;

    /**
     * Segment key -> endpoints in the quadrant's local unit frame,
     * [lx1, ly1, lx2, ly2]. (0,0) is the stem/near corner, (1,1) the far
     * outer corner. Quadrant placement and mirroring happen in Encoder.
     *
     * @var array<string, array{int, int, int, int}>
     */
    public readonly array $endpoints;

    /**
     * Digit -> bitmask over $keys, one bit per candidate segment. The same
     * convention seven-segment display firmware has used for decades, applied
     * to five segments instead of seven.
     *
     * @var list<int>
     */
    public readonly array $digitMap;

    /**
     * @param array<string, mixed> $model Decoded model.json.
     */
    public function __construct(array $model)
    {
        if (!isset($model['segments'], $model['digitMap'])) {
            throw new InvalidArgumentException('model.json is missing "segments" or "digitMap".');
        }

        $keys = [];
        $endpoints = [];

        foreach ($model['segments'] as $segment) {
            $keys[] = $segment['key'];
            $endpoints[$segment['key']] = $segment['coords'];
        }

        if (count($model['digitMap']) !== 10) {
            throw new InvalidArgumentException('model.json "digitMap" must have exactly 10 entries, one per digit.');
        }

        $this->keys = $keys;
        $this->endpoints = $endpoints;
        $this->digitMap = $model['digitMap'];
    }

    /**
     * Active segment keys for one digit, in canonical order.
     *
     * @return list<string>
     */
    public function segmentsForDigit(int $digit): array
    {
        $mask = $this->digitMap[$digit];
        $active = [];

        foreach ($this->keys as $bit => $key) {
            if ($mask & (1 << $bit)) {
                $active[] = $key;
            }
        }

        return $active;
    }
}
