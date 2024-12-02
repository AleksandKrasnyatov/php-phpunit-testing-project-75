<?php

namespace Downloader\Downloader;

use DiDom\Document;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
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
    $log->pushHandler(new StreamHandler($outputPath . "/$urlModifiedName.log"));

    $client = new $clientClass();
    $html = $client->get($url)->getBody()->getContents();

    $host = parse_url($url)['host'] ?? '';
    $filePath = "{$outputPath}/{$urlModifiedName}.html";
    if (!touch($filePath)) {
        $log->error("Something wrong when created html file '{$filePath}'");
        throw new Exception("Something wrong when created html file '{$filePath}'");
    }

    $document = new Document($html);
    processFiles($document, $client, [
        'outputPath' => $outputPath,
        'filesPath' => "{$urlModifiedName}_files",
        'url' => $url,
        'host' => $host,
        'log' => $log,
    ]);
    file_put_contents($filePath, $document->html());
    $realFilePath = realpath($filePath) ? realpath($filePath) : $filePath;
    $log->info("Page was successfully downloaded into {$realFilePath}");
    return $realFilePath;
}

/**
 * @param Document $document
 * @param $client //tests\FakeClient| GuzzleHttp\Client
 * @param array $config
 * @return void
 * @throws Throwable
 */
function processFiles(Document $document, $client, array $config): void
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
    $fullFilesDirPath = "{$outputPath}/{$filesPath}";
    if (!is_dir($fullFilesDirPath)) {
        mkdir($fullFilesDirPath, recursive: true);
    }
    foreach ($tagAttributeMapping as $tag => $attribute) {
        foreach ($document->find($tag) as $element) {
            $elementUrl = $element->getAttribute($attribute);
            if (is_null($elementUrl)) {
                continue;
            }
            if (!isAbsoluteUrl($elementUrl)) {
                $elementUrl = makeAbsoluteUrl($elementUrl, $host);
            }
            $elementUrlHost = parse_url($elementUrl)['host'] ?? '';
            if ($elementUrlHost == $host) {
                $elementUrlModifiedName = getNameFromUrl($elementUrl);
                if (getUrlWithoutScheme($elementUrl) == getUrlWithoutScheme($url)) {
                    $elementUrlModifiedName .= ".html";
                }
                $elementPath = "{$filesPath}/{$elementUrlModifiedName}";
                $client->get($elementUrl, ['sink' => "$fullFilesDirPath/{$elementUrlModifiedName}"]);
                $element->setAttribute($attribute, $elementPath);
                $log->info("Element {$elementUrl} was successfully handled, current path {$elementPath}");
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
 * @return string
 */
function getUrlWithoutScheme(string $url): string
{
    $urlParts = parse_url(trim($url, '/'));
    $host = $urlParts['host'] ?? '';
    $path = $urlParts['path'] ?? '';
    return "{$host}{$path}";
}


/**
 * @param string $url
 * @param string $host
 * @return string
 * @throws Exception
 */
function makeAbsoluteUrl(string $url, string $host): string
{
    if (str_starts_with($url, '//')) {
        return "http:{$url}";
    }
    if (str_starts_with($url, '/')) {
        return "http://{$host}{$url}";
    }
    if (str_starts_with($url, '.')) {
        $url = trim($url, '.');
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
