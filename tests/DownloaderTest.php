<?php

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
        $this->mock = new MockHandler();
        $this->client = new Client(['handler' => $this->mock]);
    }

    public function testCreateFileRecursive(): void
    {
        $directoryPath = vfsStream::url('exampleDir') . "/test/test2";
        downloadPage('https://foo.com', $directoryPath, $this->client);
        $this->assertTrue($this->root->hasChild('test'));
        $firstChild = $this->root->getChild('test');
        $this->assertTrue($firstChild->hasChild('test2'));
        $secondChild = $firstChild->getChild('test2');
        $this->assertTrue($secondChild->hasChild('foo-com.html'));
    }

    public function testFileContent(): void
    {
        $directoryPath = vfsStream::url('exampleDir');
        $responseBody = '{ "package": "guzzle" }';
        $rightResponse = new Response(200, [], $responseBody);
        $this->mock->append($rightResponse);

        downloadPage('https://foo.com', $directoryPath, $this->client);
        $file = $this->root->getChild('foo-com.html');
        $this->assertNotEmpty($file);
        $this->assertEquals($responseBody, $file->getContent());
    }
}
