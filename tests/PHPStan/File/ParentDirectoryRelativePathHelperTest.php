<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPUnit\Framework\TestCase;

class ParentDirectoryRelativePathHelperTest extends TestCase
{

	public static function dataGetRelativePath(): array
	{
		return [
			[
				'/usr/var/www',
				'/usr/var/www/test.php',
				'test.php',
			],
			[
				'/usr/var/www/foo/bar/baz',
				'/usr/var/www/test.php',
				'../../../test.php',
			],
			[
				'/',
				'/usr/var/www/test.php',
				'/usr/var/www/test.php',
			],
			[
				'/usr/var/www',
				'/usr/var/www/src/test.php',
				'src/test.php',
			],
			[
				'/usr/var/www/',
				'/usr/var/www/src/test.php',
				'src/test.php',
			],
			[
				'/usr/var/www',
				'/usr/var/test.php',
				'../test.php',
			],
			[
				'/usr/var/www/',
				'/usr/var/test.php',
				'../test.php',
			],
			[
				'/usr/var/www/',
				'/usr/var/web/test.php',
				'../web/test.php',
			],
			[
				'/usr/var/www/',
				'/usr/var/web/foo/test.php',
				'../web/foo/test.php',
			],
			[
				'/',
				'/test.php',
				'/test.php',
			],
			[
				'/var/www',
				'/usr/test.php',
				'/usr/test.php',
			],
			[
				'C:\\var',
				'C:\\var\\test.php',
				'test.php',
			],
			[
				'C:\\var',
				'C:\\var\\src\\test.php',
				'src/test.php',
			],
			[
				'C:\\var',
				'C:\\test.php',
				'../test.php',
			],
			[
				'C:\\var\\',
				'C:\\usr\\test.php',
				'../usr/test.php',
			],
			[
				'C:\\',
				'C:\\test.php',
				'test.php',
			],
			[
				'C:\\',
				'C:\\src\\test.php',
				'src/test.php',
			],
			[
				'C:\\var',
				'D:\\var\\src\\test.php',
				'D:\\var\\src\\test.php',
			],
		];
	}

	/**
	 * @dataProvider dataGetRelativePath
	 */
	public function testGetRelativePath(
		string $parentDirectory,
		string $filename,
		string $expectedRelativePath,
	): void
	{
		$helper = new ParentDirectoryRelativePathHelper($parentDirectory);
		$this->assertSame(
			$expectedRelativePath,
			$helper->getRelativePath($filename),
		);
	}

}
