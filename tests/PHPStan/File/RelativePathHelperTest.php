<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPUnit\Framework\TestCase;
use function array_map;
use function str_replace;
use function substr;

class RelativePathHelperTest extends TestCase
{

	public static function dataGetRelativePath(): array
	{
		return [
			[
				'/usr',
				[],
				'/usr/app/test.php',
				'app/test.php',
			],
			[
				'',
				['/usr'],
				'/usr/app/test.php',
				'app/test.php',
			],
			[
				'/',
				['/usr'],
				'/usr/app/test.php',
				'app/test.php',
			],
			[
				'',
				[],
				'/usr/app/test.php',
				'/usr/app/test.php',
			],
			[
				'/var',
				[],
				'/usr/app/test.php',
				'/usr/app/test.php',
			],
			[
				'/var',
				['/usr'],
				'/usr/app/test.php',
				'/usr/app/test.php',
			],
			[
				'/',
				[],
				'/usr/app/test.php',
				'/usr/app/test.php',
			],
			[
				'/',
				[
					'/usr/app/src',
					'/usr/app/tests',
				],
				'/usr/app/src/test.php',
				'src/test.php',
			],
			[
				'/',
				[
					'/usr/app/src',
					'/usr/app/tests',
				],
				'/usr/app/tests/test.php',
				'tests/test.php',
			],
			[
				'',
				[
					'/',
					'/usr/app/tests',
				],
				'/usr/app/tests/test.php',
				'/usr/app/tests/test.php',
			],
			[
				'/usr',
				[
					'/usr/app/src',
					'/usr/app/tests',
				],
				'/usr/app/src/test.php',
				'src/test.php',
			],
			[
				'/usr',
				[
					'/usr/app/src',
					'/',
				],
				'/usr/app/src/test.php',
				'/usr/app/src/test.php',
			],
			[
				'/usr',
				[
					'/usr/app/src',
					'/usr/app/tests',
				],
				'/usr/app/tests/test.php',
				'tests/test.php',
			],
			[
				'/',
				[
					'/usr/app/src/analyzed.php',
				],
				'/usr/app/src/analyzed.php',
				'analyzed.php',
			],
			[
				'/usr',
				[
					'/usr/app/src/analyzed.php',
				],
				'/usr/app/src/analyzed.php',
				'analyzed.php',
			],
			[
				'/usr/app',
				[
					'/usr/app/src/analyzed.php',
				],
				'/usr/app/src/analyzed.php',
				'analyzed.php',
			],
			[
				'/usr/app',
				[
					'/usr/app/src/analyzed.php',
					'/',
				],
				'/usr/app/src/analyzed.php',
				'/usr/app/src/analyzed.php',
			],
			[
				'/Users/ondrej/Downloads/phpstan-wtf/normal.php',
				[
					'/Users/ondrej/Downloads/phpstan-wtf/normal.php/src',
				],
				'/Users/ondrej/Downloads/phpstan-wtf/normal.php/src/index.php',
				'index.php',
			],
			[
				'/Users/ondrej/Downloads/phpstan-wtf/normal',
				[
					'/Users/ondrej/Downloads/phpstan-wtf/normal/src',
				],
				'/Users/ondrej/Downloads/phpstan-wtf/normal/src/index.php',
				'index.php',
			],
			[
				'/Users/ondrej/Downloads/phpstan-wtf/normal.php',
				[
					'/Users/ondrej/Downloads/phpstan-wtf/normal.php/src',
					'/Users/ondrej/Downloads/phpstan-wtf/normal.php/tests',
				],
				'/Users/ondrej/Downloads/phpstan-wtf/normal.php/src/index.php',
				'src/index.php',
			],
			[
				'/Users/ondrej/Downloads/phpstan-wtf/normal',
				[
					'/Users/ondrej/Downloads/phpstan-wtf/normal/src',
					'/Users/ondrej/Downloads/phpstan-wtf/normal/tests',
				],
				'/Users/ondrej/Downloads/phpstan-wtf/normal/src/index.php',
				'src/index.php',
			],
		];
	}

	/**
	 * @dataProvider dataGetRelativePath
	 * @param string[] $analysedPaths
	 */
	public function testGetRelativePathOnUnix(
		string $currentWorkingDirectory,
		array $analysedPaths,
		string $filenameToRelativize,
		string $expectedResult,
	): void
	{
		$helper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), $currentWorkingDirectory, $analysedPaths, '/');
		$this->assertSame(
			$expectedResult,
			$helper->getRelativePath($filenameToRelativize),
		);
	}

	/**
	 * @dataProvider dataGetRelativePath
	 * @param string[] $analysedPaths
	 */
	public function testGetRelativePathOnWindows(
		string $currentWorkingDirectory,
		array $analysedPaths,
		string $filenameToRelativize,
		string $expectedResult,
	): void
	{
		$sanitize = static function (string $path): string {
			if (substr($path, 0, 1) === '/') {
				return 'C:\\' . substr(str_replace('/', '\\', $path), 1);
			}

			return str_replace('/', '\\', $path);
		};
		$helper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), $sanitize($currentWorkingDirectory), array_map($sanitize, $analysedPaths), '\\');
		$this->assertSame(
			$sanitize($expectedResult),
			$helper->getRelativePath($sanitize($filenameToRelativize)),
		);
	}

	public static function dataGetRelativePathWindowsSpecific(): array
	{
		return [
			[
				'C:\www',
				[
					'C:\www\project/app/src',
					'C:\www\project/app/tests',
				],
				'C:\www\project\app\src\system\Bootstrap.php',
				'project\app\src\system\Bootstrap.php', // should be src\system\Bootstrap.php
			],
			[
				'C:\www',
				[
					'C:\www\project\app/src',
					'C:\www\project\app/tests',
				],
				'C:\www\project\app\src\system\Bootstrap.php',
				'app\src\system\Bootstrap.php', // should be src\system\Bootstrap.php
			],
		];
	}

	/**
	 * @dataProvider dataGetRelativePathWindowsSpecific
	 * @param string[] $analysedPaths
	 */
	public function testGetRelativePathWindowsSpecific(
		string $currentWorkingDirectory,
		array $analysedPaths,
		string $filenameToRelativize,
		string $expectedResult,
	): void
	{
		$helper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), $currentWorkingDirectory, $analysedPaths, '\\');
		$this->assertSame(
			$expectedResult,
			$helper->getRelativePath($filenameToRelativize),
		);
	}

}
