<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin;

class NetworkConfig
{
    /**
     * @var boolean
     */
    private bool $testnet;

    /**
     * @var string|null
     */
    public ?string $blockCypherToken = null;

    /**
     * @param array<string,mixed> $network
     */
    public function __construct(array $network)
    {
        $this->testnet = $network['testnet'] ?? false;
        $this->blockCypherToken = $network['blockCypherToken'] ?? null;
    }

    /**
     * @return boolean
     */
    public function isTestnet(): bool
    {
        return $this->testnet;
    }

    /**
     * @return string|null
     */
    public function getBlockCypherToken(): ?string
    {
        return $this->blockCypherToken;
    }
}
