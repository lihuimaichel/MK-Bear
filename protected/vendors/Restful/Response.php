<?php
/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/3
 * Time: 11:03
 */

namespace application\vendors\Restful;


class Response
{
    public function __construct($body, $statusCode, $headerString, Request $request)
    {
        $this->statusCode = $statusCode;

        $this->body = $body;
        $this->headerString = $headerString;
        $this->request = $request;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
    public function getBody()
    {
        return $this->body;
    }

}