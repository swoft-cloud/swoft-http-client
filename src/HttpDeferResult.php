<?php

namespace Swoft\Http;

use Psr\Http\Message\ResponseInterface;
use Swoft\Core\AbstractCoResult;
use Swoft\Http\Adapter\ResponseTrait;
use Swoft\Http\Message\Stream\SwooleStream;


/**
 * Http Defer Result
 *
 * @property \Swoole\Http\Client|resource $client
 */
class HttpDeferResult extends AbstractCoResult implements HttpResultInterface
{

    use ResponseTrait;

    /**
     * Return result
     *
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getResult(...$params): ResponseInterface
    {
        $client = $this->client;
        $this->recv();
        $this->sendResult = $client->body;
        $client->close();
        $headers = value(function () {
            $headers = [];
            foreach ($this->client->headers as $key => $value) {
                $exploded = explode('-', $key);
                foreach ($exploded as &$str) {
                    $str = ucfirst($str);
                }
                $ucKey = implode('-', $exploded);
                $headers[$ucKey] = $value;
            }
            unset($str);
            return $headers;
        });
        $response = $this->createResponse()
                         ->withBody(new SwooleStream($this->sendResult ?? ''))
                         ->withHeaders($headers ?? [])
                         ->withStatus($this->deduceStatusCode($client));
        return $response;
    }

    /**
     * @alias getResult()
     * @return ResponseInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getResponse(): ResponseInterface
    {
        return $this->getResult();
    }

    /**
     * Transfer sockets error code to HTTP status code.
     * TODO transfer more error code
     *
     * @param \Swoole\Http\Client $client
     * @return int
     */
    private function deduceStatusCode($client): int
    {
        if ($client->errCode === 110) {
            $status = 404;
        } else {
            $status = $client->statusCode;
        }
        return $status > 0 ? $status : 500;
    }

}