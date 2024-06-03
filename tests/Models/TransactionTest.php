<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin\Tests\Models;

use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Bitcoin\Tests\BaseTest;
use MultipleChain\Bitcoin\Models\Transaction;

class TransactionTest extends BaseTest
{
    /**
     * @var Transaction
     */
    private Transaction $tx;

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
        $this->tx = new Transaction($this->txId);
    }

    /**
     * @return void
     */
    public function testId(): void
    {
        $this->assertEquals($this->txId, $this->tx->getId());
    }

    /**
     * @return void
     */
    public function testData(): void
    {
        $this->assertIsObject($this->tx->getData());
    }

    /**
     * @return void
     */
    public function testType(): void
    {
        $this->assertEquals(TransactionType::COIN, $this->tx->getType());
    }

    /**
     * @return void
     */
    public function testWait(): void
    {
        $this->assertEquals(TransactionStatus::CONFIRMED, $this->tx->wait());
    }

    /**
     * @return void
     */
    public function testUrl(): void
    {
        $this->assertEquals(
            'https://blockstream.info/testnet/tx/' . strtolower($this->tx->getId()),
            $this->tx->getUrl()
        );
    }

    /**
     * @return void
     */
    public function testSender(): void
    {
        $this->assertEquals(strtolower($this->data->senderAddress), strtolower($this->tx->getSigner()));
    }

    /**
     * @return void
     */
    public function testFee(): void
    {
        $this->assertEquals(0.00014, $this->tx->getFee()->toFloat());
    }

    /**
     * @return void
     */
    public function testBlockNumber(): void
    {
        $this->assertEquals(2814543, $this->tx->getBlockNumber());
    }

    /**
     * @return void
     */
    public function testBlockTimestamp(): void
    {
        $this->assertEquals(1715328679, $this->tx->getBlockTimestamp());
    }

    /**
     * @return void
     */
    public function testBlockConfirmationCount(): void
    {
        $this->assertGreaterThan(13, $this->tx->getBlockConfirmationCount());
    }

    /**
     * @return void
     */
    public function testStatus(): void
    {
        $this->assertEquals(TransactionStatus::CONFIRMED, $this->tx->getStatus());
    }
}
