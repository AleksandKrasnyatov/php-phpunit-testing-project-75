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

    $document = new Document($html);
    if ($document->has('img')) {
        processImages($document, $client, ['outputPath' => $outputPath, 'imagesDirPath' => "{$urlModifiedName}_files"]);
    }
    file_put_contents($filePath, $document->html());
}

/**
 * @param Document $document
 * @param FakeClient|Client $client
 * @param string $imagesDirPath
 * @return void
 * @throws GuzzleException
 * @throws InvalidSelectorException
 */
function processImages(Document $document, FakeClient|Client $client, array $config): void
{
    $outputPath = $config['outputPath'];
    $imagesDirPath = $config['imagesDirPath'];
    $images = $document->find('img');
    $fullImagesDirPath = "{$outputPath}/{$imagesDirPath}";
    if (!is_dir($fullImagesDirPath)) {
        mkdir($fullImagesDirPath);
    }
    foreach ($images as $image) {
        $imgUrl = $image->getAttribute('src');
        if (str_contains($imgUrl, 'png') || str_contains($imgUrl, 'jpg')) {
            $imageUrlModifiedName = getNameFromUrl($imgUrl);
            $client->get($imgUrl, ['sink' => "$fullImagesDirPath/{$imageUrlModifiedName}"]);
            $image->setAttribute("src", "{$imagesDirPath}/{$imageUrlModifiedName}");
        }
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
