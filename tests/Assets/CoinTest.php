<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin\Tests\Assets;

use MultipleChain\Utils\Number;
use MultipleChain\Bitcoin\Assets\Coin;
use MultipleChain\Bitcoin\Tests\BaseTest;
use MultipleChain\Bitcoin\Models\Transaction;

class CoinTest extends BaseTest
{
    /**
     * @var Coin
     */
    private Coin $coin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->coin = new Coin();
    }

    /**
     * @return void
     */
    public function testName(): void
    {
        $this->assertEquals('Bitcoin', $this->coin->getName());
    }

    /**
     * @return void
     */
    public function testSymbol(): void
    {
        $this->assertEquals('BTC', $this->coin->getSymbol());
    }

    /**
     * @return void
     */
    public function testDecimals(): void
    {
        $this->assertEquals(8, $this->coin->getDecimals());
    }

    /**
     * @return void
     */
    public function testBalance(): void
    {
        $this->assertEquals(
            0.00003,
            $this->coin->getBalance("tb1qc240vx54n08hnhx8l4rqxjzcxf4f0ssq5asawm")->toFloat()
        );
    }

    /**
     * @return void
     */
    public function testTransfer(): void
    {
        $signer = $this->coin->transfer(
            $this->data->senderAddress,
            $this->data->receiverAddress,
            $this->data->transferAmount
        );

        $signer = $signer->sign($this->data->senderPrivateKey);

        if (!$this->data->coinTransferTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        $beforeBalance = $this->coin->getBalance($this->data->receiverAddress);

        (new Transaction($signer->send()))->wait();

        $afterBalance = $this->coin->getBalance($this->data->receiverAddress);

        $transferNumber = new Number($this->data->transferAmount);

        $this->assertEquals(
            $afterBalance->toString(),
            $beforeBalance->add($transferNumber)->toString()
        );
    }
}
