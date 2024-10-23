<?php

namespace Downloader\Downloader;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use tests\FakeClient;

/**
 * @param string $url
 * @param $outputPath
 * @param string $clientClass tests\FakeClient| GuzzleHttp\Client
 * @return void
 * @throws GuzzleException
 */
function downloadPage(string $url, $outputPath, string $clientClass): void
{
    $client = new $clientClass();
    $html = $client->get($url)->getBody()->getContents();
    mkdir($outputPath, recursive: true);
    $filePath = $outputPath . "/foo-com.html";
    touch($filePath);
    file_put_contents($filePath, $html);
////    dd($outputPath);
////    mkdir($outputPath);
}