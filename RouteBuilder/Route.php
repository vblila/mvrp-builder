<?php

namespace RouteBuilder;

use LogicException;

/**
 * Route of locations (addresses)
 */
class Route
{
    /** @var int[] Array of location indexes */
    private array $locationIndexes;

    private ?int $durationSeconds = null;

    private ?int $finedDurationSeconds = null;

    private ?int $totalWeightKg = null;

    /** @var int[] $locationIndexes */
    public function __construct(array $locationIndexes)
    {
        $this->locationIndexes = array_values($locationIndexes);
    }

    public function getLocationIndexes(): array
    {
        return $this->locationIndexes;
    }

    public function setDurationSeconds(int $seconds): Route {
        $this->durationSeconds = $seconds;
        return $this;
    }

    public function getDurationSeconds(): int
    {
        if ($this->durationSeconds === null) {
            throw new LogicException('Duration is requested in not calculated route!');
        }
        return $this->durationSeconds;
    }

    public function setFinedDurationSeconds(int $seconds): Route
    {
        $this->finedDurationSeconds = $seconds;
        return $this;
    }

    public function getFinedDurationSeconds(): int
    {
        if ($this->finedDurationSeconds === null) {
            throw new LogicException('Fined duration is requested in not calculated route!');
        }
        return $this->finedDurationSeconds;
    }

    public function setTotalWeightKg(int $kg): Route
    {
        $this->totalWeightKg = $kg;
        return $this;
    }

    public function getTotalWeightKg(): int
    {
        if ($this->totalWeightKg === null) {
            throw new LogicException('Total weight is requested in not calculated route!');
        }
        return $this->totalWeightKg;
    }

    public function isCalculated(): bool
    {
        return $this->finedDurationSeconds !== null;
    }
}
