<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin\Services;

use MultipleChain\Bitcoin\Provider;
use MultipleChain\Bitcoin\TransactionData;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Services\TransactionSignerInterface;
// BitWasp\Bitcoin
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class TransactionSigner implements TransactionSignerInterface
{
    /**
     * @var TransactionData
     */
    private TransactionData $rawData;

    /**
     * @var string
     */
    private string $signedData;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @var AddressCreator
     */
    private AddressCreator $addressCreator;

    private const FEE_LEVEL_MAP = [
        1 => 'fastestFee',
        2 => 'halfHourFee',
        3 => 'hourFee',
        4 => 'economyFee',
        5 => 'minimumFee'
    ];

    /**
     * @param mixed $rawData
     * @param Provider|null $provider
     * @return void
     */
    public function __construct(mixed $rawData, ?ProviderInterface $provider = null)
    {
        $this->rawData = $rawData;
        $this->addressCreator = new AddressCreator();
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @param string $privateKey
     * @param int $feeLevel
     * @return TransactionSignerInterface
     */
    public function sign(string $privateKey, int $feeLevel = 2): TransactionSignerInterface
    {
        Bitcoin::setNetwork(
            $this->provider->isTestnet() ? NetworkFactory::bitcoinTestnet() : NetworkFactory::bitcoin()
        );

        $utxos = $this->rawData->getUtxos();
        $transaction = TransactionFactory::build();
        $amountToSend = $this->rawData->getAmount();
        $priv = (new PrivateKeyFactory())->fromWif($privateKey);
        $to = $this->addressCreator->fromString($this->rawData->getTo());
        $from = $this->addressCreator->fromString($this->rawData->getFrom());

        $total = 0;
        /**
         * @var object{'value': int, 'txid': string, 'vout': int} $utxo
         */
        foreach ($utxos as $utxo) {
            $total += $utxo->value;
            $transaction->input($utxo->txid, $utxo->vout);
        }

        $transaction->payToAddress($amountToSend, $to);
        $fee = $this->estimateFee(count($utxos), 2, $feeLevel);
        $transaction->payToAddress($total - $amountToSend - $fee, $from);

        $signer = new Signer($transaction->get());

        $txOut = new TransactionOutput($total, $from->getScriptPubKey());

        for ($i = 0; $i < count($utxos); $i++) {
            $signer->sign($i, $priv, $txOut);
        }

        $this->signedData = $signer->get()->getHex();

        return $this;
    }

    /**
     * @param int $input
     * @param int $output
     * @param int $feeLevel
     * @return int
     */
    public function estimateFee(int $input, int $output, int $feeLevel = 2): int
    {
        if ($this->provider->isTestnet()) {
            $api = 'https://mempool.space/testnet/api/v1/fees/recommended';
        } else {
            $api = 'https://mempool.space/api/v1/fees/recommended';
        }

        $curl = curl_init($api);

        if (false !== $curl) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $res = curl_exec($curl);
            curl_close($curl);

            $res = is_bool($res) ? '' : $res;
            $fees = json_decode($res, true);
            $feeRate = $fees[self::FEE_LEVEL_MAP[$feeLevel]];
        } else {
            $feeRate = 60;
        }

        return $feeRate * (($input * 148) + ($output * 34) + 10);
    }

    /**
     * @return string Transaction id
     */
    public function send(): string
    {
        try {
            return $this->provider->createRequest('tx', $this->signedData, 'POST');
        } catch (\Throwable $th) {
            throw new \RuntimeException($th->getMessage());
        }
    }

    /**
     * @return TransactionData
     */
    public function getRawData(): TransactionData
    {
        return $this->rawData;
    }

    /**
     * @return string
     */
    public function getSignedData(): string
    {
        return $this->signedData;
    }
}
