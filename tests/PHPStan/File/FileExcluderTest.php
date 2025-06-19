<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FileExcluderTest extends PHPStanTestCase
{

	/**
	 * @param string[] $analyseExcludes
	 */
	#[DataProvider('dataExcludeOnWindows')]
	public function testFilesAreExcludedFromAnalysingOnWindows(
		string $filePath,
		array $analyseExcludes,
		bool $isExcluded,
	): void
	{
		$this->skipIfNotOnWindows();

		$fileExcluder = new FileExcluder($this->getFileHelper(), $analyseExcludes);

		$this->assertSame($isExcluded, $fileExcluder->isExcludedFromAnalysing($filePath));
	}

	public static function dataExcludeOnWindows(): array
	{
		return [
			[
				__DIR__ . '/data/excluded-file.php',
				[],
				false,
			],
			[
				__DIR__ . '/data/excluded-file.php',
				[__DIR__ . '/*'],
				true,
			],
			[
				__DIR__ . '\Foo\data\excluded-file.php',
				[__DIR__ . '/*\/data/*'],
				true,
			],
			[
				__DIR__ . '\data\func-call.php',
				[],
				false,
			],
			[
				__DIR__ . '\data\parse-error.php',
				[__DIR__ . '/*'],
				true,
			],
			[
				__DIR__ . '\data\parse-error.php',
				[__DIR__ . '/data/?a?s?-error.?h?'],
				true,
			],
			[
				__DIR__ . '\data\parse-error.php',
				[__DIR__ . '/data/[pP]arse-[eE]rror.ph[pP]'],
				true,
			],
			[
				__DIR__ . '\data\parse-error.php',
				['*/tests/PHPStan/File/data/*'],
				true,
			],
			[
				__DIR__ . '\data\parse-error.php',
				[__DIR__ . '/aaa'],
				false,
			],
			[
				'C:\Temp\data\parse-error.php',
				['C:/Temp/*'],
				true,
			],
			[
				'C:\Data\data\parse-error.php',
				['C:/Temp/*'],
				false,
			],
			[
				'c:\Temp\data\parse-error.php',
				['C:/Temp/*'],
				true,
			],
			[
				'C:\Temp\data\parse-error.php',
				['C:/temp/*'],
				true,
			],
			[
				'c:\Data\data\parse-error.php',
				['C:/Temp/*'],
				false,
			],
			[
				'c:\etc\phpstan\dummy-1.php',
				['c:\etc\phpstan\\*'],
				true,
			],
			[
				'c:\etc\phpstan-test\dummy-2.php',
				['c:\etc\phpstan\\'],
				false,
			],
			[
				'c:\etc\phpstan-test\dummy-2.php',
				['c:\etc\phpstan*'],
				true,
			],
		];
	}

	/**
	 * @param string[] $analyseExcludes
	 */
	#[DataProvider('dataExcludeOnUnix')]
	public function testFilesAreExcludedFromAnalysingOnUnix(
		string $filePath,
		array $analyseExcludes,
		bool $isExcluded,
	): void
	{
		$this->skipIfNotOnUnix();

		$fileExcluder = new FileExcluder($this->getFileHelper(), $analyseExcludes);

		$this->assertSame($isExcluded, $fileExcluder->isExcludedFromAnalysing($filePath));
	}

	public static function dataExcludeOnUnix(): array
	{
		return [
			[
				__DIR__ . '/data/excluded-file.php',
				[],
				false,
			],
			[
				__DIR__ . '/data/excluded-file.php',
				[__DIR__ . '/*'],
				true,
			],
			[
				__DIR__ . '/Foo/data/excluded-file.php',
				[__DIR__ . '/*/data/*'],
				true,
			],
			[
				__DIR__ . '/data/func-call.php',
				[],
				false,
			],
			[
				__DIR__ . '/data/parse-error.php',
				[__DIR__ . '/*'],
				true,
			],
			[
				__DIR__ . '/data/parse-error.php',
				[__DIR__ . '/data/?a?s?-error.?h?'],
				true,
			],
			[
				__DIR__ . '/data/parse-error.php',
				[__DIR__ . '/data/[pP]arse-[eE]rror.ph[pP]'],
				true,
			],
			[
				__DIR__ . '/data/parse-error.php',
				[__DIR__ . '/aaa'],
				false,
			],
			[
				'/tmp/data/parse-error.php',
				['/tmp/*'],
				true,
			],
			[
				'/home/myname/data/parse-error.php',
				['/tmp/*'],
				false,
			],
			[
				'/etc/phpstan/dummy-1.php',
				['/etc/phpstan/*'],
				true,
			],
			[
				'/etc/phpstan-test/dummy-2.php',
				['/etc/phpstan/'],
				false,
			],
			[
				'/etc/phpstan-test/dummy-2.php',
				['/etc/phpstan*'],
				true,
			],
		];
	}

	public static function dataNoImplicitWildcard(): iterable
	{
		yield [
			__DIR__ . '/tests/foo.php',
			[
				__DIR__ . '/test',
			],
			false,
		];

		yield [
			__DIR__ . '/test/foo.php',
			[
				__DIR__ . '/test',
			],
			true,
		];

		yield [
			__DIR__ . '/FileExcluderTest.php',
			[
				__DIR__ . '/FileExcluderTest.php',
			],
			true,
		];

		yield [
			__DIR__ . '/tests/foo.php',
			[
				__DIR__ . '/test*',
			],
			true,
		];
	}

	/**
	 * @param string[] $analyseExcludes
	 */
	#[DataProvider('dataNoImplicitWildcard')]
	public function testNoImplicitWildcard(
		string $filePath,
		array $analyseExcludes,
		bool $isExcluded,
	): void
	{
		$this->skipIfNotOnUnix();

		$fileExcluder = new FileExcluder($this->getFileHelper(), $analyseExcludes);

		$this->assertSame($isExcluded, $fileExcluder->isExcludedFromAnalysing($filePath));
	}

}
