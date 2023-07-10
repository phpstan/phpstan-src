<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Testing\PHPStanTestCase;

class FileHelperTest extends PHPStanTestCase
{

	/**
	 * @return string[][]
	 */
	public function dataAbsolutizePathOnWindows(): array
	{
		return [
			['C:/Program Files', 'C:/Program Files'],
			['C:\Program Files', 'C:\Program Files'],
			['Program Files', 'C:\abcd\Program Files'],
			['/home/users', 'C:\abcd\home/users'],
			['users', 'C:\abcd\users'],
			['../lib', 'C:\abcd\../lib'],
			['./lib', 'C:\abcd\./lib'],
			['vFs-v1.0://a\b', 'vFs-v1.0://a\b'],
			['./x://a\b', 'C:\abcd\./x://a\b'],
		];
	}

	/**
	 * @dataProvider dataAbsolutizePathOnWindows
	 */
	public function testAbsolutizePathOnWindows(string $path, string $absolutePath): void
	{
		$this->skipIfNotOnWindows();
		$fileHelper = new FileHelper('C:\abcd');
		$this->assertSame($absolutePath, $fileHelper->absolutizePath($path));
	}

	/**
	 * @return string[][]
	 */
	public function dataAbsolutizePathOnLinuxOrMac(): array
	{
		return [
			['C:/Program Files', '/abcd/C:/Program Files'],
			['C:\Program Files', '/abcd/C:\Program Files'],
			['Program Files', '/abcd/Program Files'],
			['/home/users', '/home/users'],
			['users', '/abcd/users'],
			['../lib', '/abcd/../lib'],
			['./lib', '/abcd/./lib'],
			['phar:///home/users/', 'phar:///home/users/'],
			['vFs-v1.0://a/b', 'vFs-v1.0://a/b'],
			['./x://a/b', '/abcd/./x://a/b'],
		];
	}

	/**
	 * @dataProvider dataAbsolutizePathOnLinuxOrMac
	 */
	public function testAbsolutizePathOnLinuxOrMac(string $path, string $absolutePath): void
	{
		$this->skipIfNotOnUnix();
		$fileHelper = new FileHelper('/abcd');
		$this->assertSame($absolutePath, $fileHelper->absolutizePath($path));
	}

	/**
	 * @return string[][]
	 */
	public function dataNormalizePathOnWindows(): array
	{
		return [
			['C:/Program Files/PHP', 'C:\Program Files\PHP'],
			['C:/Program Files/./PHP', 'C:\Program Files\PHP'],
			['C:/Program Files/../PHP', 'C:\PHP'],
			['/home/users/phpstan', '\home\users\phpstan'],
			['/home/users/./phpstan', '\home\users\phpstan'],
			['/home/users/../../phpstan/', '\phpstan'],
			['./phpstan/', 'phpstan'],
			['vFs-v1.0://a/b', 'vfs-v1.0://a\b'],
		];
	}

	/**
	 * @dataProvider dataNormalizePathOnWindows
	 */
	public function testNormalizePathOnWindows(string $path, string $normalizedPath): void
	{
		$this->skipIfNotOnWindows();
		$this->assertSame($normalizedPath, self::getContainer()->getByType(FileHelper::class)->normalizePath($path));
	}

	/**
	 * @return string[][]
	 */
	public function dataNormalizePathOnLinuxOrMac(): array
	{
		return [
			['C:\Program Files\PHP', 'C:/Program Files/PHP'],
			['C:\Program Files\.\PHP', 'C:/Program Files/PHP'],
			['C:\Program Files\..\PHP', 'C:/PHP'],
			['/home/users/phpstan', '/home/users/phpstan'],
			['/home/users/./phpstan', '/home/users/phpstan'],
			['/home/users/../../phpstan/', '/phpstan'],
			['./phpstan/', 'phpstan'],
			['vFs-v1.0://a/b', 'vfs-v1.0://a/b'],
			['phar:///usr/local/bin/phpstan.phar/tmp/cache/../..', 'phar:///usr/local/bin/phpstan.phar'],
			['phar:///usr/local/bin/phpstan.phar/tmp/cache/../../..', '/usr/local/bin'],
		];
	}

	/**
	 * @dataProvider dataNormalizePathOnLinuxOrMac
	 */
	public function testNormalizePathOnLinuxOrMac(string $path, string $normalizedPath): void
	{
		$this->skipIfNotOnUnix();
		$this->assertSame($normalizedPath, self::getContainer()->getByType(FileHelper::class)->normalizePath($path));
	}

}
