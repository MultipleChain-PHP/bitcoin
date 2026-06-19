<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin\Tests\Models;

use PHPUnit\Framework\TestCase;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Bitcoin\Models\Transaction;
use MultipleChain\Bitcoin\Tests\Support\ProviderObj;

class TransactionConfirmationTest extends TestCase
{
    /**
     * @return void
     */
    public function testBlockConfirmationCountWithNullBlockHeight(): void
    {
        $tx = $this->createTransactionWithMockedData(
            (object) ['status' => (object) []],
            102
        );

        $this->assertSame(0, $tx->getBlockConfirmationCount());
    }

    /**
     * @return void
     */
    public function testBlockConfirmationCountWithThreeConfirmations(): void
    {
        $tx = $this->createTransactionWithMockedData(
            (object) ['status' => (object) ['block_height' => 100]],
            102
        );

        $this->assertSame(3, $tx->getBlockConfirmationCount());
    }

    /**
     * @return void
     */
    public function testBlockConfirmationCountWithSameBlockAsTip(): void
    {
        $tx = $this->createTransactionWithMockedData(
            (object) ['status' => (object) ['block_height' => 100]],
            100
        );

        $this->assertSame(1, $tx->getBlockConfirmationCount());
    }

    /**
     * @return void
     */
    public function testBlockConfirmationCountClampsNegativeToZero(): void
    {
        $tx = $this->createTransactionWithMockedData(
            (object) ['status' => (object) ['block_height' => 100]],
            99
        );

        $this->assertSame(0, $tx->getBlockConfirmationCount());
    }

    /**
     * @return void
     */
    public function testStatusConfirmedWhenConfirmedTrue(): void
    {
        $tx = $this->createTransactionWithMockedData(
            (object) [
                'status' => (object) [
                    'block_height' => 100,
                    'confirmed' => true,
                ],
            ],
            100
        );

        $this->assertSame(TransactionStatus::CONFIRMED, $tx->getStatus());
    }

    /**
     * @return void
     */
    public function testStatusFailedWhenConfirmedFalse(): void
    {
        $tx = $this->createTransactionWithMockedData(
            (object) [
                'status' => (object) [
                    'block_height' => 100,
                    'confirmed' => false,
                ],
            ],
            100
        );

        $this->assertSame(TransactionStatus::FAILED, $tx->getStatus());
    }

    /**
     * @return void
     */
    public function testStatusPendingWhenBlockHeightMissing(): void
    {
        $tx = $this->createTransactionWithMockedData(
            (object) ['status' => (object) []],
            100
        );

        $this->assertSame(TransactionStatus::PENDING, $tx->getStatus());
    }

    /**
     * @param object|null $data
     * @param int|null $latestBlock
     * @return Transaction
     */
    private function createTransactionWithMockedData(?object $data, ?int $latestBlock): Transaction
    {
        $provider = new ProviderObj(['testnet' => true], $latestBlock);

        $tx = new Transaction('test-tx-id', $provider);

        $reflection = new \ReflectionClass($tx);
        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setAccessible(true);
        $dataProperty->setValue($tx, $data);

        return $tx;
    }
}
