<?php
namespace GitQuery;

use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;

class HttpConnector extends Connector
{

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var \GuzzleHttp\Message\RequestInterface
     */
    private $request;

    /**
     * @var \GuzzleHttp\Message\ResponseInterface
     */
    private $response;

    /**
     * @var string
     */
    private $url;

    const PATH_SUFFIX = '/info/refs';

    public function __construct($url)
    {
        $parts = parse_url($url);
        if ((($parts['scheme'] !== 'https') && ($parts['scheme'] !== 'http')) || ! isset($parts['host'])) {
            throw new \InvalidArgumentException($url.' cannot be understood as HTTP(S) URL');
        }
        $this->url = $url;
        $this->client = new Client();
    }

    public function read($length)
    {
        if (! $this->response) {
            $request = $this->client->createRequest('GET', $this->url . self::PATH_SUFFIX);
            $request->setQuery(array('service' => $this->process));
            $this->response = $this->client->send($request);
        }

        // clear used request so it cannot be confused when writing
        $this->request = null;

        return $this->response->getBody()->read($length);
    }

    public function write($data)
    {
        if (! $this->request) {
            $this->request = $this->client->createRequest('POST', $this->url . DS . $this->process);
            $this->request->setBody(Stream::factory(fopen('php://temp', 'w+')));
        }

        return $this->request->getBody()->write($data);
    }

    public function flush()
    {
        $request = $this->request;
        $this->response = $request ? $this->client->send($request) : null;
    }
}
