<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function dirname;

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
			[
				'C:\Project\themes\foo\node_modules\baz.php',
				['C:/Project/themes/*/node_modules/'],
				true,
			],
			[
				'C:\Project\themes\foo\node_modules\baz.php',
				['C:/Project/themes/*/node_modules'],
				true,
			],
			[
				'C:\Project\themes\foo\node_modules-old\baz.php',
				['C:/Project/themes/*/node_modules'],
				false,
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
			[
				'/project/themes/foo/node_modules/bar/baz.php',
				['/project/themes/*/node_modules/'],
				true,
			],
			[
				'/project/themes/foo/node_modules/bar/baz.php',
				['/project/themes/*/node_modules'],
				true,
			],
			[
				'/project/themes/foo/node_modules-old/baz.php',
				['/project/themes/*/node_modules'],
				false,
			],
			[
				'/project/themes/foo/node_modules-old/baz.php',
				['/project/themes/*/node_modules/'],
				false,
			],
			[
				'/project/themes/foo/src/baz.php',
				['/project/themes/*/node_modules'],
				false,
			],
			[
				'/project/src/FooTest.php',
				['/project/src/*Test.php'],
				true,
			],
			[
				'/project/src/Foo.php',
				['/project/src/*Test.php'],
				false,
			],
			[
				'/project/src/FooTest/Bar.php',
				['/project/src/*Test'],
				true,
			],
			[
				'/project/src/FooTest',
				['/project/src/*Test'],
				true,
			],
			[
				'/project/src/FooTests/Bar.php',
				['/project/src/*Test'],
				false,
			],
			[
				'/project/src/Foo1/Bar.php',
				['/project/src/Foo?'],
				true,
			],
			[
				'/project/src/FooA/Bar.php',
				['/project/src/Foo[AB]'],
				true,
			],
			[
				'/project/src/FooC/Bar.php',
				['/project/src/Foo[AB]'],
				false,
			],
		];
	}

	/**
	 * @param string[] $analyseExcludes
	 */
	#[DataProvider('dataDirectoryExcludedOnUnix')]
	public function testDirectoryIsExcludedOnlyWhenEveryFileUnderItIs(
		string $directory,
		array $analyseExcludes,
		bool $isExcluded,
	): void
	{
		$this->skipIfNotOnUnix();

		$fileExcluder = new FileExcluder($this->getFileHelper(), $analyseExcludes);

		$this->assertSame($isExcluded, $fileExcluder->isDirectoryExcludedFromAnalysing($directory));
	}

	public static function dataDirectoryExcludedOnUnix(): iterable
	{
		yield 'the excluded directory' => [__DIR__, [__DIR__], true];
		yield 'a directory under it' => [__DIR__ . '/sub', [__DIR__], true];
		yield 'its parent' => [dirname(__DIR__), [__DIR__], false];
		yield 'a sibling with the same prefix' => [__DIR__ . 'Suffix', [__DIR__], false];
		yield 'a pattern for everything under it' => ['/project/src/Foo/node_modules', ['/project/src/*/node_modules/*'], true];
		yield 'a directory under one a pattern excludes' => ['/project/src/Foo/node_modules/a/b', ['/project/src/*/node_modules/*'], true];
		yield 'a pattern with a bracket expression' => ['/project/src/Foo/node_modules', ['/project/src/[F]oo/node_modules/*'], true];
		yield 'a pattern whose star absorbs the separator' => ['/project/src/Foo/node_modules', ['*/node_modules*'], true];
		yield 'a pattern for everything in a directory' => ['/project/src', ['/project/src/*'], true];
		yield 'a pattern for the directory' => ['/project/src/Foo/node_modules', ['/project/src/*/node_modules'], true];
		yield 'a pattern for the directory with a trailing separator' => ['/project/src/Foo/node_modules', ['/project/src/*/node_modules/'], true];
		yield 'a pattern for a directory with a longer name' => ['/project/src/Foo/node_modules2', ['/project/src/*/node_modules'], false];
		yield 'a pattern for a directory elsewhere' => ['/project/src/Foo/other', ['/project/src/*/node_modules/*'], false];
		yield 'a pattern for some files only' => ['/project/src/Foo', ['/project/src/*/*.php'], false];
		yield 'a literal file' => [__DIR__, [__FILE__], false];
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
