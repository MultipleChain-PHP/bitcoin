<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin\Tests\Models;

use MultipleChain\Utils;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Bitcoin\Tests\BaseTest;
use MultipleChain\Bitcoin\Models\CoinTransaction;

class CoinTransactionTest extends BaseTest
{
    /**
     * @var CoinTransaction
     */
    private CoinTransaction $tx;

    /**
     * @var string
     */
    private string $txId = '335c8a251e5f18121977c3159f46983d5943325abccc19e4718c49089553d60c';

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new CoinTransaction($this->txId);
    }

    /**
     * @return void
     */
    public function testReceiver(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getReceiver()),
            strtolower($this->data->receiverAddress)
        );
    }

    /**
     * @return void
     */
    public function testSender(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getSender()),
            strtolower($this->data->senderAddress)
        );
    }

    /**
     * @return void
     */
    public function testAmount(): void
    {
        $this->assertEquals(
            $this->tx->getAmount()->toString(),
            Utils::toString($this->data->transferAmount, 8)
        );
    }

    /**
     * @return void
     */
    public function testType(): void
    {
        $this->assertEquals(
            $this->tx->getType(),
            TransactionType::COIN
        );
    }

    /**
     * @return void
     */
    public function testVerifyTransfer(): void
    {
        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->receiverAddress,
                $this->data->transferAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::OUTGOING,
                $this->data->senderAddress,
                $this->data->transferAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->senderAddress,
                $this->data->transferAmount
            ),
            TransactionStatus::FAILED
        );
    }
}
