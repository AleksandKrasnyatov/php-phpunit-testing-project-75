<?php

namespace tests;

use Exception;

class FakeClientWithException extends FakeClient
{
    /**
     * @throws Exception
     */
    public function get(string $url, array $options = []): static
    {
        throw new Exception('test exception when get is running with error');
    }
}
