<?php

namespace tests;

use Exception;

/**
 * @property string $response
 */
class FakeClient
{
    public function __construct()
    {
        $fixtureFilePath = __DIR__ . "/fixtures/responseBody.txt";
        if (file_exists(__DIR__ . "/fixtures/responseBody.txt")) {
            $this->response = file_get_contents($fixtureFilePath);
        } else {
            throw new Exception("Fixture file not found: " . $fixtureFilePath);
        }
    }

    public function get(string $url)
    {
        return $this;
    }

    public function getBody()
    {
        return $this;
    }

    public function getContents()
    {
        return $this->response;
    }
}