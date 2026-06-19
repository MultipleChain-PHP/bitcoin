<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin\Tests\Support;

use MultipleChain\Bitcoin\Provider;

class ProviderObj extends Provider
{
    private ?int $tipHeight;

    /**
     * @param array<string,mixed> $network
     * @param int|null $tipHeight
     */
    public function __construct(array $network, ?int $tipHeight = null)
    {
        parent::__construct($network);
        $this->tipHeight = $tipHeight;
    }

    /**
     * @param string $endpoint
     * @param mixed $data
     * @param string $method
     * @return mixed
     */
    public function createRequest(string $endpoint, mixed $data = null, string $method = 'GET'): mixed
    {
        if ('blocks/tip/height' === $endpoint) {
            return $this->tipHeight;
        }

        return null;
    }
}
