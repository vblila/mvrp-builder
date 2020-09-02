<?php

namespace RouteBuilder;

class RouteCollection {
    /** @var int[][] */
    private array $locationIndexesSpace;

    /** @var Route[] */
    private ?array $routes;

    private ?int $durationSeconds = null;

    private ?int $finedDurationSeconds = null;

    /**
     * @param Route[] $routes
     * @param int $maxExecutorsCount
     */
    public function __construct(array $routes, int $maxExecutorsCount)
    {
        $this->routes = $routes;

        $this->locationIndexesSpace = [];
        for ($i = 0; $i < count($routes); $i++) {
            $this->locationIndexesSpace[] = $routes[$i]->getLocationIndexes();
        }

        while (count($this->locationIndexesSpace) < $maxExecutorsCount) {
            $this->locationIndexesSpace[] = array_fill(0, count($routes[0]->getLocationIndexes()), 0);
        }
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        if ($this->routes === null) {
            $routes = [];
            for ($i = 0; $i < count($this->locationIndexesSpace); $i++) {
                $route = $this->getRouteFromLocationIndexesRow($this->locationIndexesSpace[$i]);
                if ($route === null) {
                    continue;
                }

                $routes[] = $route;
            }

            $this->routes = $routes;
        }

        return $this->routes;
    }

    public function getFinedDurationSeconds(): int
    {
        if ($this->finedDurationSeconds === null) {
            $finedDuration = 0;
            $routes = $this->getRoutes();
            for ($i = 0; $i < count($routes); $i++) {
                $finedDuration += $routes[$i]->getFinedDurationSeconds();
            }

            $this->finedDurationSeconds = $finedDuration;
        }

        return $this->finedDurationSeconds;
    }

    public function getDurationSeconds(): int
    {
        if ($this->durationSeconds === null) {
            $duration = 0;
            $routes = $this->getRoutes();
            for ($i = 0; $i < count($routes); $i++) {
                $duration += $routes[$i]->getDurationSeconds();
            }

            $this->durationSeconds = $duration;
        }

        return $this->durationSeconds;
    }

    public function hasFine(): bool
    {
        return $this->getFinedDurationSeconds() > $this->getDurationSeconds();
    }

    /**
     * @return int[][]
     */
    public function getLocationIndexesSpace(): array
    {
        return $this->locationIndexesSpace;
    }

    /**
     * @param array $locationIndexesRow
     * @return Route|null
     */
    private function getRouteFromLocationIndexesRow(array $locationIndexesRow): ?Route {
        $points = [0];
        for ($i = 1; $i < count($locationIndexesRow); $i++) {
            if ($locationIndexesRow[$i] === 0) {
                continue;
            }
            $points[] = $locationIndexesRow[$i];
        }

        return count($points) > 1 ? new Route($points) : null;
    }

    public function setLocationIndexInRoute(int $point, int $pointIndex, int $rowIndex): RouteCollection {
        $this->routes = null;
        $this->finedDurationSeconds = null;

        $this->locationIndexesSpace[$rowIndex][$pointIndex] = $point;
        return $this;
    }
}
