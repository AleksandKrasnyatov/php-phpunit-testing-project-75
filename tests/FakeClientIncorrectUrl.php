<?php

namespace tests;

class FakeClientIncorrectUrl extends FakeClient
{
    public string $fixtureFilePath = __DIR__ . "/fixtures/incorrectUrlAttribute.txt";
}
