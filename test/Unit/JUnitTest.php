<?php

declare(strict_types=1);

namespace ParaTest\Tests\Unit;

use ParaTest\JUnit\LogMerger;
use ParaTest\JUnit\Writer;
use ParaTest\Tests\TmpDirCreator;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

use function file_get_contents;
use function file_put_contents;
use function glob;
use function str_replace;

/**
 * @internal
 *
 * @covers \ParaTest\JUnit\LogMerger
 * @covers \ParaTest\JUnit\MessageType
 * @covers \ParaTest\JUnit\TestCase
 * @covers \ParaTest\JUnit\TestCaseWithMessage
 * @covers \ParaTest\JUnit\TestSuite
 * @covers \ParaTest\JUnit\Writer
 */
final class JUnitTest extends TestCase
{
    public function testMergeJunitLogs(): void
    {
        $tmpDir = (new TmpDirCreator())->create();

        $junitFiles = [];
        foreach (glob(FIXTURES . '/common_results/junit/*') as $file) {
            $junitFiles[] = new SplFileInfo($file);
        }

        self::assertNotSame([], $junitFiles);
        $testSuite = (new LogMerger())->merge($junitFiles);

        $outputFile = $tmpDir . '/result.xml';
        (new Writer())->write(
            $testSuite,
            $outputFile,
        );

        $xml = file_get_contents($outputFile);
        $xml = str_replace('time="8.641969"', 'time="1.234567"', $xml);
        file_put_contents($outputFile, $xml);

        self::assertXmlFileEqualsXmlFile(FIXTURES . '/common_results/combined.xml', $outputFile);
    }

    public function testHandleSpecialChars(): void
    {
        $tmpDir = (new TmpDirCreator())->create();

        $junitLog  = FIXTURES . '/special_chars/data-provider-with-special-chars.xml';
        $testSuite = (new LogMerger())->merge([new SplFileInfo($junitLog)]);

        $outputFile = $tmpDir . '/result.xml';
        (new Writer())->write(
            $testSuite,
            $outputFile,
        );

        self::assertXmlFileEqualsXmlFile($junitLog, $outputFile);
    }
}
