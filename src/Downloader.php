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
    $host = parse_url($url)['host'] ?? '';
    $urlModifiedName = getNameFromUrl($url);
    $filePath = "{$outputPath}/{$urlModifiedName}.html";
    touch($filePath);

    $document = new Document($html);
    processFiles($document, $client, [
        'outputPath' => $outputPath,
        'filesPath' => "{$urlModifiedName}_files",
        'url' => $url,
        'host' => $host,
    ]);
    file_put_contents($filePath, $document->html());

    $realFilePath = realpath($filePath);
    echo "Page was successfully downloaded into {$realFilePath}\n";
}

/**
 * @param Document $document
 * @param FakeClient|Client $client
 * @param array $config
 * @return void
 * @throws GuzzleException
 * @throws InvalidSelectorException
 */
function processFiles(Document $document, FakeClient|Client $client, array $config): void
{
    $tagAttributeMapping = [
        'img' => 'src',
        'link' => 'href',
        'script' => 'src',
    ];

    $outputPath = $config['outputPath'];
    $filesPath = $config['filesPath'];
    $host = $config['host'];
    $url = $config['url'];
    $fullImagesDirPath = "{$outputPath}/{$filesPath}";
    if (!is_dir($fullImagesDirPath)) {
        mkdir($fullImagesDirPath);
    }

    foreach ($tagAttributeMapping as $tag => $attribute) {
        foreach ($document->find($tag) as $element) {
            $elementUrl = $element->getAttribute($attribute);
            if (str_contains($elementUrl, $host)) {
                $elementUrlModifiedName = getNameFromUrl($elementUrl);
                $elementPath = "{$filesPath}/{$elementUrlModifiedName}";
                if (trim($elementUrl, '/') == $url) {
                    $elementPath .= ".html";
                } else {
                    $client->get($elementUrl, ['sink' => "$fullImagesDirPath/{$elementUrlModifiedName}"]);
                }
                $element->setAttribute($attribute, $elementPath);
            }
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
