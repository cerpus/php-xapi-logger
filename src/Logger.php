<?php

namespace Cerpus\xAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

class Logger
{
    /** @var string */
    protected $key;

    /** @var string */
    protected $secret;

    /** @var string */
    protected $server;

    /** @var Client */
    protected $client;

    /** @var int */
    protected $errorCode = null;

    /** @var string */
    protected $errorMessage = '';

    public function __construct(
        $key = null,
        $secret = null,
        $server = 'https://learninglocker.cerpus-course.com/data/xAPI'
    ) {
        if (!$key || !$secret) {
            throw new \InvalidArgumentException('key and secret is required');
        }

        $this->key = $key;
        $this->secret = $secret;
        $this->server = $server;

        $this->client = new Client([
            'base_uri' => $this->server,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Experience-API-Version' => '1.0.1',
            ],
            'auth' => [$this->key, $this->secret], // Basic auth
            'timeout' => 30.0, // 30 seconds
        ]);
    }

    /**
     * @param Statement $statement
     * @return bool
     */
    public function log(Statement $statement): bool
    {
        try {
            $this->resetErrorState();
            $success = false;

            $content = '[' . $statement->asJsonString() . ']';

            $response = $this->client->request('POST', 'statements', [
                'body' => $content,
            ]);

            $success = true;
        } catch (\Throwable $exception) {
            $success = false;
            $this->errorCode = $exception->getCode();
            $this->errorMessage = $exception->getMessage();
        }

        return $success;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    protected function resetErrorState()
    {
        $this->errorCode = null;
        $this->errorMessage = '';
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->errorCode !== null;
    }

    /**
     * @return int|null
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
