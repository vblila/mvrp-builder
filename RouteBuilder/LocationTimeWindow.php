<?php

namespace RouteBuilder;

/**
 * Delivery time window of location (address)
 */
class LocationTimeWindow
{
    /** @var int Start time of window in seconds from the beginning of the day */
    private int $startTimeSeconds;

    /** @var int Finish time of window in seconds from the beginning of the day */
    private int $finishTimeSeconds;

    public function __construct(int $startTimeSeconds, int $finishTimeSeconds)
    {
        $this->startTimeSeconds  = $startTimeSeconds;
        $this->finishTimeSeconds = $finishTimeSeconds;
    }

    public function getStartTimeSeconds(): int
    {
        return $this->startTimeSeconds;
    }

    public function getFinishTimeSeconds(): int
    {
        return $this->finishTimeSeconds;
    }
}
