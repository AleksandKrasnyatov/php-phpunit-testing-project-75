<?php

namespace Downloader\Downloader;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use tests\FakeClient;

/**
 * @param string $url
 * @param $outputPath
 * @param string $clientClass tests\FakeClient| GuzzleHttp\Client
 * @return void
 * @throws GuzzleException
 * @throws InvalidSelectorException
 */
function downloadPage(string $url, $outputPath, string $clientClass): void
{
    $client = new $clientClass();
    $html = $client->get($url)->getBody()->getContents();
    if (!is_dir($outputPath)) {
        mkdir($outputPath, recursive: true);
    }
    $urlModifiedName = getNameFromUrl($url);
    $filePath = "{$outputPath}/{$urlModifiedName}.html";
    touch($filePath);
    file_put_contents($filePath, $html);

    $document = new Document($html);
    if ($document->has('img')) {
        $imagesDirPath = "{$outputPath}/{$urlModifiedName}_files";
        $elements = $document->find('img');
        downloadImages($imagesDirPath, $elements, $client);
    }
}

/**
 * @param string $dirPath
 * @param array $urls
 * @param FakeClient|Client $client
 * @return void
 * @throws GuzzleException
 */
function downloadImages(string $dirPath, array $urls, FakeClient|Client $client): void
{
    if (!is_dir($dirPath)) {
        mkdir($dirPath);
    }
    foreach ($urls as $url) {
        $imgUrl = $url->getAttribute('src');
        $imageUrlModifiedName = getNameFromUrl($imgUrl);
        $client->get($imgUrl, ['sink' => "{$dirPath}/{$imageUrlModifiedName}"]);
    }
}

/**
 * @param string $url
 * @return string
 */
function getNameFromUrl(string $url): string
{
    $urlParts = parse_url($url);
    $modifiedHost = str_replace('.', '-', $urlParts['host'] ?? '');
    $modifiedPath = str_replace(['/', '_'], '-', $urlParts['path'] ?? '');
    return "{$modifiedHost}{$modifiedPath}";
}
