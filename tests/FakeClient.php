<?php

namespace tests;

use Exception;

/**
 * @property string $response
 */
class FakeClient
{
    public string|false $response;
    public string $fixtureFilePath = __DIR__ . "/fixtures/parsedBody.txt";

    public function __construct(array $config = [])
    {
        if (file_exists($this->fixtureFilePath)) {
            $this->response = file_get_contents($this->fixtureFilePath);
        } else {
            throw new Exception("Fixture file not found: " . $this->fixtureFilePath);
        }
    }

    public function get(string $url, array $options = []): static
    {
        $tagPathMapping = [
            'png' => __DIR__ . "/fixtures/mrstar.png",
            'css' => __DIR__ . "/fixtures/test.css",
            'js' => __DIR__ . "/fixtures/test.js",
        ];

        if (array_key_exists('sink', $options)) {
            touch($options['sink']);
            foreach ($tagPathMapping as $tag => $path) {
                if (str_contains($url, $tag)) {
                    $content = file_get_contents($path);
                }
            }
            file_put_contents($options['sink'], $content ?? '');
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
