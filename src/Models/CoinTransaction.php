<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin\Models;

use MultipleChain\Utils\Number;
use MultipleChain\Bitcoin\Utils;
use MultipleChain\Bitcoin\Assets\Coin;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\Models\CoinTransactionInterface;

class CoinTransaction extends Transaction implements CoinTransactionInterface
{
    /**
     * Extract the payout address of a single output (vout entry).
     * @param mixed $output A transaction output
     * @return string
     */
    private function outputAddress(mixed $output): string
    {
        // @phpcs:ignore
        return $output->scriptpubkey_address ?? '';
    }

    /**
     * Convert a satoshi amount into a coin-denominated Number.
     * @param int $satoshi Amount in satoshi
     * @return Number
     */
    private function satoshiToAmount(int $satoshi): Number
    {
        return new Number(Utils::fromSatoshi($satoshi), (new Coin())->getDecimals());
    }

    /**
     * Sum the value (in satoshi) of every output paying to the given address.
     * Bitcoin allows an address to appear in multiple outputs, so we sum
     * instead of picking the first match.
     * @param array<mixed> $outputs Transaction outputs (vout)
     * @param string $address Wallet address to total
     * @return int Total value in satoshi
     */
    private function sumOutputsFor(array $outputs, string $address): int
    {
        $target = strtolower($address);
        $total = 0;
        foreach ($outputs as $output) {
            if (strtolower($this->outputAddress($output)) === $target) {
                $total += (int) ($output->value ?? 0);
            }
        }
        return $total;
    }

    /**
     * @return string
     */
    public function getReceiver(): string
    {
        $outputs = $this->getData()?->vout ?? [];
        $sender = strtolower($this->getSender());
        // The receiver is the first output that is not change back to the sender.
        foreach ($outputs as $output) {
            if (strtolower($this->outputAddress($output)) !== $sender) {
                return $this->outputAddress($output);
            }
        }
        return $this->outputAddress($outputs[0] ?? null);
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->getSigner();
    }

    /**
     * @param string|null $address Address to total for. When null, defaults to
     *   the most likely receiver (first output not returning to the sender).
     *   When it equals the sender, returns the amount sent out of the wallet;
     *   otherwise the total paid to that address.
     * @return Number
     */
    public function getAmount(?string $address = null): Number
    {
        $outputs = $this->getData()?->vout ?? [];
        $sender = strtolower($this->getSender());

        $address = strtolower($address ?? $this->getReceiver());

        // Amount sent by the wallet: everything that did not return as change.
        if ($address === $sender) {
            $outgoing = array_filter(
                $outputs,
                fn ($output) => strtolower($this->outputAddress($output)) !== $sender
            );
            // Self-transfer (everything returns to the sender): fall back to total.
            $relevant = count($outgoing) > 0 ? $outgoing : $outputs;
            $total = 0;
            foreach ($relevant as $output) {
                $total += (int) ($output->value ?? 0);
            }
            return $this->satoshiToAmount($total);
        }

        // Total paid to this specific address (summed across all its outputs).
        return $this->satoshiToAmount($this->sumOutputsFor($outputs, $address));
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param float $amount
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, float $amount): TransactionStatus
    {
        $status = $this->getStatus();

        if (TransactionStatus::PENDING === $status) {
            return TransactionStatus::PENDING;
        }

        if (AssetDirection::INCOMING === $direction) {
            // A single tx (e.g. an exchange batch withdrawal) can pay many
            // addresses at once, so there is no single "receiver" to pick.
            // Verify how much $address actually received, wherever it sits
            // among the outputs. (For the sender this is only its change, which
            // is why verifying an incoming transfer to the sender fails.)
            $outputs = $this->getData()?->vout ?? [];
            $received = $this->satoshiToAmount($this->sumOutputsFor($outputs, $address));
            if ($received->toFloat() !== $amount) {
                return TransactionStatus::FAILED;
            }
        } else {
            if (strtolower($this->getSender()) !== strtolower($address)) {
                return TransactionStatus::FAILED;
            }
            // Amount the wallet sent out (getAmount treats the sender specially).
            if ($this->getAmount($address)->toFloat() !== $amount) {
                return TransactionStatus::FAILED;
            }
        }

        return TransactionStatus::CONFIRMED;
    }
}
