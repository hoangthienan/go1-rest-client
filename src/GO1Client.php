<?php

namespace GO1;

class GO1Client
{
    private $jwt;
    public  $apiEndpoint = 'https://api.go1.com';
    public  $verifySsl   = true;

    private $requestSuccessful = false;
    private $lastError         = '';
    private $lastResponse      = [];
    private $lastRequest       = [];

    public function __construct($jwt)
    {
        $this->jwt = $jwt;
        $this->lastResponse = ['headers' => null, 'body' => null];
    }

    public function success()
    {
        return $this->requestSuccessful;
    }

    public function getLastError()
    {
        return $this->lastError ?: false;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    public function get($method, $args = [], $timeout = 200)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }

    private function makeRequest($httpVerb, $method, $args = [], $timeout = 200)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception("cURL support is required, but can't be found.");
        }

        $url = $this->apiEndpoint . '/' . $method;
        $this->lastRequest = [
            'method'  => $httpVerb,
            'path'    => $method,
            'url'     => $url,
            'body'    => '',
            'timeout' => $timeout,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->jwt,
            ]
        );
        curl_setopt($ch, CURLOPT_USERAGENT, 'GO1/RestClient/1.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        switch ($httpVerb) {
            case 'get':
                $query = http_build_query($args);
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
                break;
        }

        $response['body'] = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);

        if (isset($response['headers']['request_header'])) {
            $this->lastRequest['headers'] = $response['headers']['request_header'];
        }

        if ($response['body'] === false) {
            $this->lastError = curl_error($ch);
        }

        curl_close($ch);

        return $this->formatResponse($response);
    }

    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        $this->lastRequest['body'] = $encoded;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    private function formatResponse($response)
    {
        $this->lastResponse = $response;
        if (!empty($response['body'])) {
            $d = json_decode($response['body'], true);
            if (isset($d['error'])) {
                $this->lastError = sprintf('%s: %s', $d['error'], $d['error_description']);
            } else {
                $this->requestSuccessful = true;
            }

            return $d;
        }

        return false;
    }
}
