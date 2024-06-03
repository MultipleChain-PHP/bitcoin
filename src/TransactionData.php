<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin;

class TransactionData
{
    /**
     * @var string
     */
    private string $from;

    /**
     * @var string
     */
    private string $to;

    /**
     * @var int
     */
    private int $amount;

    /**
     * @var array<object>
     */
    private array $utxos;

    /**
     * @param string $from
     * @return TransactionData
     */
    public function setFrom(string $from): TransactionData
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @param string $to
     * @return TransactionData
     */
    public function setTo(string $to): TransactionData
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @param int $amount
     * @return TransactionData
     */
    public function setAmount(int $amount): TransactionData
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param array<object> $utxos
     * @return TransactionData
     */
    public function setUtxos(array $utxos): TransactionData
    {
        $this->utxos = $utxos;
        return $this;
    }

    /**
     * @return array<object>
     */
    public function getUtxos(): array
    {
        return $this->utxos;
    }
}
