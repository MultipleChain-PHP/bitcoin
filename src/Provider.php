<?php

declare(strict_types=1);

namespace MultipleChain\Bitcoin;

use MultipleChain\Enums\ErrorType;
use MultipleChain\Interfaces\ProviderInterface;

class Provider implements ProviderInterface
{
    /**
     * @var NetworkConfig
     */
    public NetworkConfig $network;

    /**
     * @var string
     */
    public string $api;

    /**
     * @var string
     */
    public string $explorer;

    /**
     * @var string
     */
    public string $wsUrl;

    /**
     * @var string|null
     */
    public ?string $blockCypherToken = null;

    /**
     * @var string
     */
    public string $defaultBlockCypherToken = '49d43a59a4f24d31a9731eb067ab971c';

    /**
     * @var Provider|null
     */
    private static ?Provider $instance;

    /**
     * @param array<string,mixed> $network
     */
    public function __construct(array $network)
    {
        $this->update($network);
    }

    /**
     * @return Provider
     */
    public static function instance(): Provider
    {
        if (null === self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_NOT_INITIALIZED->value);
        }
        return self::$instance;
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public static function initialize(array $network): void
    {
        if (null !== self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_ALREADY_INITIALIZED->value);
        }
        self::$instance = new self($network);
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public function update(array $network): void
    {
        self::$instance = $this;
        $this->network = new NetworkConfig($network);
        $this->blockCypherToken = $this->network->getBlockCypherToken();
        if ($this->isTestnet()) {
            $this->api = 'https://blockstream.info/testnet/api/';
            $this->explorer = 'https://blockstream.info/testnet/';
            $token = $this->blockCypherToken ?? $this->defaultBlockCypherToken;
            $this->wsUrl = 'wss://socket.blockcypher.com/v1/btc/test3?token=' . $token;
        } else {
            $this->api = 'https://blockstream.info/api/';
            $this->explorer = 'https://blockstream.info/';
            if (null !== $this->blockCypherToken) {
                $this->wsUrl = 'wss://socket.blockcypher.com/v1/btc/main?token=' . $this->blockCypherToken;
            } else {
                $this->wsUrl = 'wss://ws.blockchain.info/inv';
            }
        }
    }

    /**
     * @param string $endpoint
     * @return string
     */
    public function createEndpoint(string $endpoint): string
    {
        return $this->api . $endpoint;
    }

    /**
     * @param string $endpoint
     * @param mixed $data
     * @param string $method
     * @return mixed
     */
    public function createRequest(string $endpoint, mixed $data = null, string $method = 'GET'): mixed
    {
        if ('GET' === $method && is_array($data)) {
            $endpoint .= '?' . http_build_query($data);
        }

        $curl = curl_init($this->createEndpoint($endpoint));

        if (false === $curl) {
            return null;
        }

        $method = strtoupper($method);

        $reqData = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method
        ];

        if ('POST' === $method) {
            $reqData[CURLOPT_POSTFIELDS] = is_array($data) ? json_encode($data) : $data;
        }

        curl_setopt_array($curl, $reqData);

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);

        if (false === $response || 200 !== $info['http_code']) {
            preg_match('/\{.*\}/', $response, $matches);
            if (!empty($matches)) {
                $jsonString = $matches[0];
                $errorData = json_decode($jsonString, true);

                if (JSON_ERROR_NONE === json_last_error()) {
                    throw new \RuntimeException($errorData['message'], $errorData['code']);
                } else {
                    throw new \RuntimeException(json_last_error_msg());
                }
            } else {
                throw new \RuntimeException('Request failed');
            }
        }

        if (false === $response) {
            return null;
        }

        curl_close($curl);

        return $this->isJson($response) ? json_decode($response) : $response;
    }

    /**
     * @param string $string
     * @return bool
     */
    private function isJson(string $string): bool
    {
        json_decode($string);
        return JSON_ERROR_NONE === json_last_error();
    }

    /**
     * @return bool
     */
    public function isTestnet(): bool
    {
        return $this->network->isTestnet();
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkRpcConnection(?string $url = null): bool
    {
        return true;
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkWsConnection(?string $url = null): bool
    {
        return true;
    }
}
