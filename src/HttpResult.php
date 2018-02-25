<?php

namespace Swoft\Http;

use Psr\Http\Message\ResponseInterface;
use Swoft\Core\AbstractDataResult;
use Swoft\Http\Adapter\ResponseTrait;
use Swoft\Http\Message\Stream\SwooleStream;

/**
 * Http Result
 */
class HttpResult extends AbstractDataResult implements HttpResultInterface
{
    use ResponseTrait;

    /**
     * @var resource
     */
    public $client;

    /**
     * Return result
     *
     * @param array $params
     * @return string
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getResult(...$params): string
    {
        $response = $this->getResponse(...$params);
        return $response->getBody()->getContents();
    }

    /**
     * @alias getResult()
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getResponse(...$params): ResponseInterface
    {
        $client = $this->client;

        $status = curl_getinfo($client, CURLINFO_HTTP_CODE);
        $headers = curl_getinfo($client);
        curl_close($client);
        $response = $this->createResponse()
                         ->withBody(new SwooleStream($this->data ?? ''))
                         ->withStatus($status)
                         ->withHeaders($headers ?? []);

        return $response;
    }

}
