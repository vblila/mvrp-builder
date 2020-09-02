<?php

namespace RouteBuilder;

use LogicException;

/**
 * Probabilist route builder finds optimal routes for multiple vehicles visiting a set of locations (Vehicle Routing Problem)
 */
class ProbabilisticRouteBuilder
{
    /** @var LocationTimeWindow[] */
    private array $locationTimeWindows;

    /** @var int[][] */
    private array $durationSecondsMatrix;

    /** @var int[]|null */
    private ?array $locationPickupWeights;

    private int $maxExecutors;

    private ?int $maxExecutorWeight;

    private float $buildDurationSeconds = 0.0;

    private ?float $buildStartTimeSeconds = null;

    private float $buildDurationLimitSeconds;

    /**
     * @param LocationTimeWindow[] $locationTimeWindows
     * @param int[][] $durationSecondsMatrix
     * @param int[]|null $locationPickupWeightsKg
     * @param int $maxExecutors
     * @param int|null $maxExecutorWeight
     * @param float $buildDurationLimitSeconds
     */
    public function __construct(
        array $locationTimeWindows,
        array $durationSecondsMatrix,
        ?array $locationPickupWeightsKg,
        int $maxExecutors,
        ?int $maxExecutorWeight,
        float $buildDurationLimitSeconds = 5.0
    ) {
        $this->locationTimeWindows = $locationTimeWindows;
        $this->durationSecondsMatrix = $durationSecondsMatrix;
        $this->locationPickupWeights = $locationPickupWeightsKg;
        $this->maxExecutors = $maxExecutors;
        $this->maxExecutorWeight = $maxExecutorWeight;
        $this->buildDurationLimitSeconds = $buildDurationLimitSeconds;
    }

    public function getBuildDurationSeconds(): float
    {
        return round($this->buildDurationSeconds, 4);
    }

    public function calculateRouteData(Route $route): ProbabilisticRouteBuilder
    {
        if ($route->isCalculated()) {
            throw new LogicException('Calculate is requested in calculated route!');
        }

        $locationIndexes = $route->getLocationIndexes();

        $durationSeconds = 0;
        $finedDurationSeconds = 0;
        $totalWeightKg = 0;

        $calculatedLocationTimeSeconds = 0;
        for ($i = 0; $i < count($locationIndexes); $i++) {
            $locationIndex = $locationIndexes[$i];

            $startWindowSeconds  = $this->locationTimeWindows[$locationIndex]->getStartTimeSeconds();
            $finishWindowSeconds = $this->locationTimeWindows[$locationIndex]->getFinishTimeSeconds();

            $totalWeightKg += $this->locationPickupWeights[$locationIndex] ?? 0;

            if ($i === 0) {
                $calculatedLocationTimeSeconds = $startWindowSeconds;

                /*
                 * We take into account that the pick-up point for couriers takes 30 minutes of time.
                 * This time must be taken into account in the model.
                 * Without this time, the number of routes grows for free, but in practice this is not the case.
                 */
                $pickupDurationSeconds = 1800;

                $finedDurationSeconds += $pickupDurationSeconds;
                $durationSeconds += $pickupDurationSeconds;
                $calculatedLocationTimeSeconds += $pickupDurationSeconds;

                continue;
            }

            $locationDurationSeconds = $this->durationSecondsMatrix[$locationIndexes[$i - 1]][$locationIndexes[$i]];

            $finedDurationSeconds += $locationDurationSeconds;
            $durationSeconds += $locationDurationSeconds;
            $calculatedLocationTimeSeconds += $locationDurationSeconds;

            if ($calculatedLocationTimeSeconds < $startWindowSeconds) {
                $calculatedLocationTimeSeconds = $startWindowSeconds;
            } elseif ($calculatedLocationTimeSeconds > $finishWindowSeconds) {
                // Time fine for late
                $locationLatenessDurationSeconds = $calculatedLocationTimeSeconds - $finishWindowSeconds;
                $finedDurationSeconds += $locationLatenessDurationSeconds * 100;
            }

            // Time fine for overweight
            if ($this->maxExecutorWeight !== null && $totalWeightKg > $this->maxExecutorWeight) {
                $finedDurationSeconds += ($totalWeightKg - $this->maxExecutorWeight) * $locationDurationSeconds;
            }
        }

        $route
            ->setTotalWeightKg($totalWeightKg)
            ->setDurationSeconds($durationSeconds)
            ->setFinedDurationSeconds($finedDurationSeconds);

        return $this;
    }

    private function mutateRoutes(RouteCollection $routeCollection, int $rowSpaceLimit): RouteCollection {
        $locationIndexesSpace = $routeCollection->getLocationIndexesSpace();

        $row1 = mt_rand(0, $rowSpaceLimit);
        $row1LocationIndexes = $locationIndexesSpace[$row1];

        /*
         * It's task about multiple vehicle routing problem .
         * All routes start from one warehouse (location has zero index).
         */
        $col1 = mt_rand(1, count($row1LocationIndexes) - 1);

        $locationIndex1 = $row1LocationIndexes[$col1];
        $locationIndex2 = null;

        while ($locationIndex2 === null || $locationIndex1 === $locationIndex2) {
            $row2 = mt_rand(0, $rowSpaceLimit);
            $row2LocationIndexes = $locationIndexesSpace[$row2];
            $col2 = mt_rand(1, count($row2LocationIndexes) - 1);

            $locationIndex2 = $row2LocationIndexes[$col2];
        }

        $newRouteCollection = clone $routeCollection;
        $newRouteCollection->setLocationIndexInRoute($locationIndex1, $col2, $row2);
        $newRouteCollection->setLocationIndexInRoute($locationIndex2, $col1, $row1);

        return $newRouteCollection;
    }

    public function searchRoutes(): ?RouteCollection {
        $this->buildStartTimeSeconds = microtime(true);
        $locationIndexes = array_keys($this->locationTimeWindows);

        if (count($locationIndexes) <= 1) {
            return null;
        }

        if (count($locationIndexes) === 2) {
            $route = new Route($locationIndexes);
            $this->calculateRouteData($route);
            return new RouteCollection([$route], 1);
        }

        $baseRoute = new Route($locationIndexes);
        $this->calculateRouteData($baseRoute);
        $winner = $this->recursiveSearchRouteCollection(
            new RouteCollection([$baseRoute], $this->maxExecutors),
            count($locationIndexes) * 10,
            2
        );

        $this->buildDurationSeconds = (float) (microtime(true) - $this->buildStartTimeSeconds);

        return $winner;
    }

    private function recursiveSearchRouteCollection(
        RouteCollection $routeCollection,
        int $iterationsLimit,
        int $depth,
        ?int $maxRowSpaceIndex = null
    ): RouteCollection {
        if ($depth <= 0) {
            return $routeCollection;
        }

        if ($this->isBuildTimeExceeded()) {
            return $routeCollection;
        }

        $best = $routeCollection;
        $maxRowSpaceIndex = $maxRowSpaceIndex ?? 0;

        for ($i = 0; $i < $iterationsLimit; $i++) {
            /*
             * Finding the best route for a smaller number of executors is more difficult in practice
             * if the search is carried out at once in the entire available executor space.
             *
             * It is necessary to search by linearly increasing the search space limit.
             */
            if (($i + 1) % (max(1, $iterationsLimit / $this->maxExecutors)) === 0) {
                $maxRowSpaceIndex++;
                $maxRowSpaceIndex = min($maxRowSpaceIndex, $this->maxExecutors - 1);
            }

            $possible = $this->mutateRoutes($best, $maxRowSpaceIndex);

            for ($r = 0; $r < count($possible->getRoutes()); $r++) {
                $this->calculateRouteData($possible->getRoutes()[$r]);
            }

            if ($depth > 1) {
                $possible = $this->recursiveSearchRouteCollection($possible, $iterationsLimit, $depth - 1, $maxRowSpaceIndex);
            }

            if ($possible->getFinedDurationSeconds() < $best->getFinedDurationSeconds()) {
                $best = $possible;
            }

            if ($this->isBuildTimeExceeded()) {
                return $best;
            }
        }

        return $best;
    }

    private function isBuildTimeExceeded(): bool
    {
        return (microtime(true) - $this->buildStartTimeSeconds) > $this->buildDurationLimitSeconds;
    }
}
