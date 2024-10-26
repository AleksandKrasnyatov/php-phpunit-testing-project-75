<?php

namespace Downloader\Downloader;

use GuzzleHttp\Exception\GuzzleException;

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
    if (!is_dir($outputPath)) {
        mkdir($outputPath, recursive: true);
    }
    $fileName = getNameFromUrl($url);
    $filePath = $outputPath . "/{$fileName}";
    touch($filePath);
    file_put_contents($filePath, $html);
}

/**
 * @param string $url
 * @return string
 */
function getNameFromUrl(string $url): string
{
    $urlParts = parse_url($url);
    $modifiedHost = str_replace('.', '-', $urlParts['host']);
    return "$modifiedHost.html";
}
