<?php

namespace Downloader\Downloader;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use tests\FakeClient;
use Throwable;

/**
 * @param string $url
 * @param string $outputPath
 * @param string $clientClass tests\FakeClient| GuzzleHttp\Client
 * @return string
 * @throws Throwable
 */
function downloadPage(string $url, string $outputPath, string $clientClass): string
{
        if (!is_dir($outputPath)) {
            if (!mkdir($outputPath, recursive: true)) {
                throw new Exception("Something wrong when created directory '$outputPath'");
            }
        }

        $log = new Logger("downloading - {$url}");
        $urlModifiedName = getNameFromUrl($url);
        $log->pushHandler(new StreamHandler(realpath($outputPath) . "/$urlModifiedName.log"));

        $client = new $clientClass();
        $html = $client->get($url)->getBody()->getContents();

        $host = parse_url($url)['host'] ?? '';
        $filePath = "{$outputPath}/{$urlModifiedName}.html";
        touch($filePath);

        $document = new Document($html);
        processFiles($document, $client, [
            'outputPath' => $outputPath,
            'filesPath' => "{$urlModifiedName}_files",
            'url' => $url,
            'host' => $host,
            'log' => $log,
        ]);
        file_put_contents($filePath, $document->html());
        $realFilePath = realpath($filePath);
        $log->info("Page was successfully downloaded into {$realFilePath}");
        return "Page was successfully downloaded into {$realFilePath}\n";
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

    $log = $config['log'];
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
            try {
                $elementUrl = $element->getAttribute($attribute);
                if (is_null($elementUrl)) {
                    continue;
                }
                if (!isAbsoluteUrl($elementUrl)) {
                    $elementUrl = makeAbsoluteUrl($elementUrl, $host);
                }
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
            } catch (Exception $exception) {
                $log->error($exception->getMessage());
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
    $urlParts = parse_url(trim($url, '/'));
    $modifiedHost = str_replace('.', '-', $urlParts['host'] ?? '');
    $modifiedPath = str_replace(['/', '_'], '-', $urlParts['path'] ?? '');
    return "{$modifiedHost}{$modifiedPath}";
}

/**
 * @param string $url
 * @param string $host
 * @return string
 * @throws Exception
 */
function makeAbsoluteUrl(string $url, string $host): string
{
    if (str_contains($url, '//')) {
        return "http:{$url}";
    }
    if (str_contains($url, '/')) {
        return "http://{$host}{$url}";
    }
    throw new Exception("$url is not a relative path, but needed to be");
}

/**
 * @param string $url
 * @return bool
 */
function isAbsoluteUrl(string $url): bool
{
    return str_contains($url, '://');
}
