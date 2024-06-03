<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Bitcoin\Utils;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Bitcoin\Provider;
use MultipleChain\Bitcoin\TransactionData;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\CoinInterface;
use MultipleChain\Bitcoin\Services\TransactionSigner;

class Coin implements CoinInterface
{
    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param Provider|null $provider
     */
    public function __construct(?ProviderInterface $provider = null)
    {
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Bitcoin';
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return 'BTC';
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return 8;
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        $result = $this->provider->createRequest('address/' . $owner);

        if (is_null($result)) {
            return new Number(0);
        }
        // @phpcs:ignore
        $stat = $result->chain_stats;
        // @phpcs:ignore
        $sat = $stat->funded_txo_sum - $stat->spent_txo_sum;
        return new Number(Utils::fromSatoshi($sat), $this->getDecimals());
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param float $amount
     * @return TransactionSigner
     */
    public function transfer(string $sender, string $receiver, float $amount): TransactionSigner
    {
        if ($amount < 0) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        if ($amount > $this->getBalance($sender)->toFloat()) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        if (strtolower($sender) === strtolower($receiver)) {
            throw new \RuntimeException(ErrorType::INVALID_ADDRESS->value);
        }

        $sat = Utils::toSatoshi($amount);
        $utxos = $this->provider->createRequest('address/' . $sender . '/utxo');

        return new TransactionSigner(
            (new TransactionData())
            ->setFrom($sender)
            ->setTo($receiver)
            ->setAmount($sat)
            ->setUtxos($utxos)
        );
    }
}
