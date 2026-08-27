<?php

declare(strict_types=1);

namespace Cistercian;

use InvalidArgumentException;

/**
 * Typed wrapper around the `places` and `quadrants` halves of model.json.
 *
 * Holds no table of its own -- the four placements live in model.json at the
 * repo root. `places` order is the order Encoder walks quadrants in, and the
 * fixtures depend on it.
 *
 * Renderers must not use this class. They consume Encoder output only.
 */
final class Quadrant
{
    /** @var list<string> Quadrant names, least significant place first. */
    public readonly array $names;

    /** @var array<string, array{placeValue: int, flipX: bool, flipY: bool}> */
    public readonly array $placements;

    /**
     * @param array<string, mixed> $model Decoded model.json.
     */
    public function __construct(array $model)
    {
        if (!isset($model['places'], $model['quadrants'])) {
            throw new InvalidArgumentException('model.json is missing "places" or "quadrants".');
        }

        foreach ($model['places'] as $place) {
            if (!isset($model['quadrants'][$place])) {
                throw new InvalidArgumentException(
                    sprintf('model.json lists place "%s" with no matching quadrant.', $place)
                );
            }
        }

        $this->names = $model['places'];
        $this->placements = $model['quadrants'];
    }

    /** @return array{placeValue: int, flipX: bool, flipY: bool} */
    public function placement(string $name): array
    {
        return $this->placements[$name];
    }
}
