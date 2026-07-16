<?php

namespace App\Repositories\Contracts;

interface MetricsRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array;

    /**
     * @param array<string, mixed> $insights
     */
    public function increment(array $insights): void;
}
