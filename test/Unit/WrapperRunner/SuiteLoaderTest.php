<?php

declare(strict_types=1);

namespace ParaTest\Tests\Unit\WrapperRunner;

use ParaTest\Tests\TestBase;
use ParaTest\WrapperRunner\SuiteLoader;
use Symfony\Component\Console\Output\BufferedOutput;

use function array_shift;
use function uniqid;

/**
 * @internal
 *
 * @covers \ParaTest\WrapperRunner\SuiteLoader
 */
final class SuiteLoaderTest extends TestBase
{
    private BufferedOutput $output;

    protected function setUpTest(): void
    {
        $this->output = new BufferedOutput();
    }

    public function testLoadTestsuiteFileFromConfig(): void
    {
        $this->bareOptions['--configuration'] = $this->fixture('phpunit-common_results.xml');

        $loader = $this->loadSuite();

        static::assertSame(7, $loader->testCount);
        static::assertCount(7, $loader->files);
    }

    public function testLoadFileGetsPathOfFile(): void
    {
        $path                      = $this->fixture('common_results' . DS . 'SuccessTest.php');
        $this->bareOptions['path'] = $path;
        $files                     = $this->loadSuite()->files;

        static::assertStringContainsString(array_shift($files), $path);
    }

    public function testCacheIsWarmedWhenSpecified(): void
    {
        $this->bareOptions['path']              = $this->fixture('common_results' . DS . 'SuccessTest.php');
        $this->bareOptions['--coverage-php']    = $this->tmpDir . DS . uniqid('result_');
        $this->bareOptions['--coverage-filter'] = $this->fixture('common_results');
        $this->bareOptions['--cache-directory'] = $this->tmpDir;
        $this->loadSuite();

        static::assertStringContainsString('Warming cache', $this->output->fetch());
    }

    private function loadSuite(?string $cwd = null): SuiteLoader
    {
        $options = $this->createOptionsFromArgv($this->bareOptions, $cwd);

        return new SuiteLoader($options, $this->output);
    }
}
