<?php

namespace tests;

use Exception;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Throwable;

use function Downloader\Downloader\downloadPage;
use function Downloader\Downloader\getNameFromUrl;
use function Downloader\Downloader\isAbsoluteUrl;
use function Downloader\Downloader\makeAbsoluteUrl;
use function Downloader\Downloader\getUrlWithoutScheme;

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
     * @throws Throwable
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
     * @throws Throwable
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
     * @throws Exception
     */
    public function testMakeAbsoluteUrlFunction(): void
    {
        $this->assertEquals('foo.com', getUrlWithoutScheme('http://foo.com'));
        $this->assertEquals('foo.com/hello', getUrlWithoutScheme('https://foo.com/hello'));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testGetUrlWithoutSchemeFunction(): void
    {
        $this->assertEquals('http://foo.com', makeAbsoluteUrl('//foo.com', ''));
        $this->assertEquals('http://foo.com/hello', makeAbsoluteUrl('/hello', 'foo.com'));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('hello is not a relative path, but needed to be');
        makeAbsoluteUrl('hello', 'foo.com');
    }

    /**
     * @return void
     */
    public function testIsAbsoluteUrlFunction(): void
    {
        $this->assertTrue(isAbsoluteUrl('https://foo.com'));
        $this->assertTrue(isAbsoluteUrl('http://foo.com'));
        $this->assertFalse(isAbsoluteUrl('//foo.com'));
        $this->assertFalse(isAbsoluteUrl('/foo.com'));
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
     * @throws Throwable
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
     * @throws Throwable
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

    /**
     * @return void
     * @throws Throwable
     */
    public function testMkDirExceptions(): void
    {
        $directoryPath = $this->root->url() . "/for_block_test";
        mkdir($directoryPath, 0000);
        $outputPath = $directoryPath . "/blocked";
        $this->expectExceptionMessage("Something wrong when created directory '$outputPath'");
        downloadPage('https://foo.com', $outputPath, FakeClient::class);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testCreateFileExceptions(): void
    {
        $outputPath = $this->root->url() . '/blocked';
        mkdir($outputPath, 0000);
        $this->expectExceptionMessage("Something wrong when created html file '{$outputPath}/foo-com.html'");
        downloadPage('https://foo.com', $outputPath, FakeClient::class);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testIncorrectSrcUrlException(): void
    {
        $this->expectExceptionMessage("foo.com/wrong/url.css is not a relative path, but needed to be");
        downloadPage('https://foo.com', vfsStream::url('exampleDir'), FakeClientIncorrectUrl::class);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testClientGetException(): void
    {
        $this->expectException(Exception::class);
        downloadPage('https://foo.com', vfsStream::url('exampleDir'), FakeClientWithException::class);
    }
}
