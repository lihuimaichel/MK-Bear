<?php
/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/3
 * Time: 10:23
 */

namespace application\vendors\Restful;


class Request
{
    protected $uri;
    protected $method = 'GET';
    protected $allowRedirect = true;
    protected $maxRedirect = 10;
    protected $timeout = 60;
    protected $headers = array();
    protected $formParams;


    const JSON_CONTENT_TYPE = 'application/json';


    private function __construct(array $params = array())
    {
        foreach ($params as $key => $value) {
            $this->$key = $value;
        }
    }

    public static function getInstance()
    {
        return new self();
    }

    public static function get($uri)
    {
        $request = self::getInstance();
        $request->withMethod("GET");
        $request->withUri($uri);
        return $request;
    }

    public static function post($uri, array $postData)
    {
        $request = self::getInstance();
        $request->withMethod("POST");
        $request->withUri($uri);

        $request->withPayload($postData);

        return $request;
    }

    public function withMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function withUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    public function withAttach($attach, $filed = 'file')
    {
        if (file_exists($attach) && is_readable($attach)) {

            if (function_exists('curl_file_create')) {
                $file = curl_file_create($attach);
            } else { //
                $file = '@' . realpath($attach);
            }
            $this->formParams[$filed] = $file;
        }
        return $this;
    }

    public function withPayload(array $data)
    {
        $this->formParams = $data;
        return $this;
    }

    public static function put($uri)
    {

    }

    public static function delete($uri)
    {

    }

    public function withHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function send()
    {
        $ch = $this->init();
        $info = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerString = substr($info, 0, $headerSize);
        $body = substr($info, $headerSize);
        curl_close($ch);

        if (strpos($contentType, self::JSON_CONTENT_TYPE) !== false) {
            $body = json_decode($body, true);
        }
        return new Response($body, $statusCode, $headerString, $this);
    }


    private function init()
    {
        if (!$this->uri) {
            throw new \Exception('Attempting to send a request before defining a URI endpoint.');
        }

        $ch = curl_init($this->uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        if ($this->method === "HEAD") {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }
        if ($this->timeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        }
        if ($this->allowRedirect) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->maxRedirect);
        }

        if ($this->formParams) {
            $postData = $this->formParams;
            if (in_array(self::JSON_CONTENT_TYPE, $this->headers)) {
                $postData = \json_encode($this->formParams);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->headers) {
            $headers = array();
            foreach($this->headers as $key=> $value) {
                $headers[] = $key.":".$value;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_HEADER, 1);

        return $ch;
    }
}
