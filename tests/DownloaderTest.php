<?php

namespace tests;

use DiDom\Exceptions\InvalidSelectorException;
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
     * @throws GuzzleException|InvalidSelectorException
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
     * @throws GuzzleException|InvalidSelectorException
     */
    public function testFileContent(): void
    {
        $expectedFilePath = __DIR__ . "/fixtures/responseBody.txt";
        $directoryPath = vfsStream::url('exampleDir');
        downloadPage('https://foo.com', $directoryPath, FakeClient::class);
        $this->assertFileEquals($expectedFilePath, $directoryPath . "/foo-com.html");
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
     * @throws GuzzleException|InvalidSelectorException
     */
    public function testImagesLogic(): void
    {
        $expectedFilePath = __DIR__ . "/fixtures/mrstar.png";
        $directoryPath = vfsStream::url('exampleDir');
        downloadPage('https://foo.com', $directoryPath, FakeClient::class);
        $newFilePath = "{$directoryPath}/foo-com_files/foo-com-assets-mrstar.png";
        $this->assertFileEquals($expectedFilePath, $newFilePath);
    }

    /**
     * @throws GuzzleException|InvalidSelectorException
     */
    public function testLocalResourcesLogic(): void
    {
        $expectedCssPath = __DIR__ . "/fixtures/test.css";
        $expectedJsPath = __DIR__ . "/fixtures/test.js";
        $directoryPath = vfsStream::url('exampleDir');
        downloadPage('https://foo.com', $directoryPath, FakeClient::class);
        $newCssPath = "{$directoryPath}/foo-com_files/foo-com-assets-application.css";
        $newJsPath = "{$directoryPath}/foo-com_files/foo-com-packs-js-runtime.js";
        $this->assertFileEquals($expectedCssPath, $newCssPath);
        $this->assertFileEquals($expectedJsPath, $newJsPath);
    }
}
