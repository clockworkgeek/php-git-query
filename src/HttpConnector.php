<?php
namespace GitQuery;

use Guzzle\Http\Client;

class HttpConnector extends Connector
{

    /**
     * @var \Guzzle\Http\Client
     */
    private $client;

    /**
     * @var resource
     */
    private $request;

    /**
     * @var \Guzzle\Http\Message\Response
     */
    private $response;

    const PATH_SUFFIX = 'info/refs';

    public function __construct($url)
    {
        $parts = parse_url($url);
        if ((($parts['scheme'] !== 'https') && ($parts['scheme'] !== 'http')) || ! isset($parts['host'])) {
            throw new \InvalidArgumentException($url.' cannot be understood as HTTP(S) URL');
        }
        $this->client = new Client($url);
    }

    public function read($length)
    {
        if (! $this->response) {
            $request = $this->client->get(self::PATH_SUFFIX.'?service='.$this->process);
            $this->response = $request->send();
            $this->response->getBody()->rewind();
            $responseType = $this->response->getContentType();
            if (strpos($responseType, 'application/x-'.$this->process) === false) {
                throw new \UnexpectedValueException($responseType.' does not match expected response type. Dumb protocol is not supported.');
            }
        }

        // clear used request so it cannot be confused when writing
        $this->request = null;

        return $this->response->getBody()->read($length);
    }

    public function write($data)
    {
        if (! $this->request) {
            $this->request = fopen('php://temp', 'w+');
        }

        return fwrite($this->request, $data);
    }

    public function flush()
    {
        if ($this->request) {
            rewind($this->request);
            $request = $this->client->post($this->process, array(
                'Content-Type' => "application/x-{$this->process}-request"
            ), $this->request);
            $this->response = $request->send();
        }
        else {
            $this->response = null;
        }
    }
}
