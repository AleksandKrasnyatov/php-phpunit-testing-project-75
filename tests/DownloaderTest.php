<?php

namespace tests;

use GuzzleHttp\Exception\GuzzleException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

use function Downloader\Downloader\downloadPage;
use function Downloader\Downloader\getNameFromUrl;

class DownloaderTest extends TestCase
{
    /**
     * @var  vfsStreamDirectory
     */
    private vfsStreamDirectory $root;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->root = vfsStream::setup('exampleDir');
    }

    /**
     * @throws GuzzleException
     */
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

    /**
     * @throws GuzzleException
     */
    public function testFileContent(): void
    {
        $expectedFilePath = __DIR__ . "/fixtures/responseBody.txt";
        $directoryPath = vfsStream::url('exampleDir');
        downloadPage('https://foo.com', $directoryPath, FakeClient::class);
        $this->assertFileExists($directoryPath . "/foo-com.html");
        $this->assertFileEquals($directoryPath . "/foo-com.html", $expectedFilePath);
    }

    /**
     * @return void
     */
    public function testGetNameFromUrlFunction(): void
    {
        $this->assertEquals('foo-com', getNameFromUrl('https://foo.com'));
        $this->assertEquals('foo-com-courses-data', getNameFromUrl('https://foo.com/courses/data'));
    }

    /**
     * @throws GuzzleException
     */
    public function testImagesLogic(): void
    {
        $expectedFilePath = __DIR__ . "/fixtures/mrstar.png";
        $directoryPath = vfsStream::url('exampleDir');
        downloadPage('https://foo.com', $directoryPath, FakeClient::class);
        $newFilePath = "{$directoryPath}/foo-com_files/fixtures-mrstar.png";
        $this->assertFileExists($newFilePath);
        $this->assertFileEquals($newFilePath, $expectedFilePath);
    }
}
