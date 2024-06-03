<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin\Models;

use MultipleChain\Utils\Number;
use MultipleChain\Bitcoin\Utils;
use MultipleChain\Enums\ErrorType;
use MultipleChain\Bitcoin\Provider;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Models\TransactionInterface;

class Transaction implements TransactionInterface
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var mixed
     */
    private mixed $data = null;

    /**
     * @var int
     */
    private int $counter = 0;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param string $id
     * @param Provider|null $provider
     */
    public function __construct(string $id, ?ProviderInterface $provider = null)
    {
        $this->id = $id;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        if (!is_null($this->data)) {
            return $this->data;
        }

        try {
            $data = $this->provider->createRequest('tx/' . $this->id);

            if (is_null($data)) {
                return null;
            }

            if (is_string($data) && $this->checkNewTransactionNotFoundPossibleError($data)) {
                return $this->getData();
            }

            return $this->data = $data;
        } catch (\Throwable $th) {
            if ($this->checkNewTransactionNotFoundPossibleError($th->getMessage())) {
                return $this->getData();
            }
            throw new \RuntimeException(ErrorType::RPC_REQUEST_ERROR->value);
        }
    }

    /**
     * @param string $message
     * @throws \RuntimeException
     * @return bool
     */
    private function checkNewTransactionNotFoundPossibleError(string $message): bool
    {
        if (false !== strpos($message, 'Transaction not found')) {
            if ($this->counter > 5) {
                throw new \RuntimeException(ErrorType::TRANSACTION_NOT_FOUND->value);
            }
            sleep(2);
            $this->counter++;
            return true;
        }
        return false;
    }

    /**
     * @param int|null $ms
     * @return TransactionStatus
     */
    public function wait(?int $ms = 4000): TransactionStatus
    {
        try {
            $status = $this->getStatus();
            if (TransactionStatus::PENDING != $status) {
                return $status;
            }

            sleep($ms / 1000);

            return $this->wait($ms);
        } catch (\Throwable $th) {
            return TransactionStatus::FAILED;
        }
    }

    /**
     * @return TransactionType
     */
    public function getType(): TransactionType
    {
        return TransactionType::COIN;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->provider->explorer . 'tx/' . $this->id;
    }

    /**
     * @return string
     */
    public function getSigner(): string
    {
        return $this->getData()?->vin[0]->prevout->scriptpubkey_address ?? '';
    }

    /**
     * @return Number
     */
    public function getFee(): Number
    {
        return new Number(Utils::fromSatoshi($this->getData()?->fee ?? 0), 8);
    }

    /**
     * @return int
     */
    public function getBlockNumber(): int
    {
        return $this->getData()?->status->block_height ?? 0;
    }

    /**
     * @return int
     */
    public function getBlockTimestamp(): int
    {
        return $this->getData()?->status?->block_time ?: 0;
    }

    /**
     * @return int
     */
    public function getBlockConfirmationCount(): int
    {
        if (is_null($data = $this->getData())) {
            return 0;
        }

        $latestBlock = $this->provider->createRequest('blocks/tip/height');
        return (int) (intval($latestBlock) - $data->status?->block_height ?: 0);
    }

    /**
     * @return TransactionStatus
     */
    public function getStatus(): TransactionStatus
    {
        $data = $this->getData();
        if (is_null($data)) {
            return TransactionStatus::PENDING;
        }

        if (isset($data?->status?->block_height)) {
            if (isset($data?->status?->confirmed)) {
                return TransactionStatus::CONFIRMED;
            } else {
                return TransactionStatus::FAILED;
            }
        }
        return TransactionStatus::PENDING;
    }
}
