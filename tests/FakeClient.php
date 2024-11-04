<?php

namespace tests;

use Exception;

/**
 * @property string $response
 */
class FakeClient
{
    public string|false $response;

    public function __construct()
    {
        $fixtureFilePath = __DIR__ . "/fixtures/parsedBody.txt";
        if (file_exists($fixtureFilePath)) {
            $this->response = file_get_contents($fixtureFilePath);
        } else {
            throw new Exception("Fixture file not found: " . $fixtureFilePath);
        }
    }

    public function get(string $url, array $options = []): static
    {
        if (array_key_exists('sink', $options)) {
            touch($options['sink']);
            $content = file_get_contents(__DIR__ . "/fixtures/mrstar.png");
            file_put_contents($options['sink'], $content);
        }
        return $this;
    }

    public function getBody(): static
    {
        return $this;
    }

    public function getContents(): string
    {
        return $this->response;
    }
}
