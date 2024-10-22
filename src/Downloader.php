<?php

namespace Downloader\Downloader;

function downloadPage(string $url, string $outputPath, $clientClass = ''): void
{
    mkdir($outputPath,
        recursive: true
    );
    touch($outputPath . "/foo-com.html");
//    dd($outputPath);
//    mkdir($outputPath);
}