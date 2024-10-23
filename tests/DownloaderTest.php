<?php

namespace tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use function Downloader\Downloader\downloadPage;

class DownloaderTest extends TestCase
{
    /**
     * @var  vfsStreamDirectory
     */
    private $root;
    public $client;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void
    {
        $this->root = vfsStream::setup('exampleDir');
    }

    public function testCreateFileRecursive(): void
    {
        $directoryPath = vfsStream::url('exampleDir') . "/test/test2";
        downloadPage('https://foo.com', $directoryPath, FakeClient::class);
        $this->assertTrue($this->root->hasChild('test'));
        $firstChild = $this->root->getChild('test');
        $this->assertTrue($firstChild->hasChild('test2'));
        $secondChild = $firstChild->getChild('test2');
        $this->assertTrue($secondChild->hasChild('foo-com.html'));
    }

    public function testFileContent(): void
    {
        $expectedFilePath = __DIR__ . "/fixtures/responseBody.txt";
        $directoryPath = vfsStream::url('exampleDir');
//        $responseBody = '{ "package": "guzzle" }';
//        $rightResponse = new Response(200, [], $responseBody);
//        $this->mock->append($rightResponse);

        downloadPage('https://foo.com', $directoryPath, FakeClient::class);
        $file = $this->root->getChild('foo-com.html');
        $this->assertFileExists($directoryPath . "/foo-com.html");
        $this->assertFileEquals($directoryPath . "/foo-com.html", $expectedFilePath);
//        $this->assertEquals($responseBody, $file->getContent());
    }
}
