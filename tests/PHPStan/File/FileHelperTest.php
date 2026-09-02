<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FileHelperTest extends PHPStanTestCase
{

	/**
	 * @return string[][]
	 */
	public static function dataAbsolutizePathOnWindows(): array
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

	#[DataProvider('dataAbsolutizePathOnWindows')]
	public function testAbsolutizePathOnWindows(string $path, string $absolutePath): void
	{
		$this->skipIfNotOnWindows();
		$fileHelper = new FileHelper('C:\abcd');
		$this->assertSame($absolutePath, $fileHelper->absolutizePath($path));
	}

	/**
	 * @return string[][]
	 */
	public static function dataAbsolutizePathOnLinuxOrMac(): array
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

	#[DataProvider('dataAbsolutizePathOnLinuxOrMac')]
	public function testAbsolutizePathOnLinuxOrMac(string $path, string $absolutePath): void
	{
		$this->skipIfNotOnUnix();
		$fileHelper = new FileHelper('/abcd');
		$this->assertSame($absolutePath, $fileHelper->absolutizePath($path));
	}

	/**
	 * @return string[][]
	 */
	public static function dataNormalizePathOnWindows(): array
	{
		return [
			['C:/Program Files/PHP', 'C:\Program Files\PHP'],
			['C:/Program Files/./PHP', 'C:\Program Files\PHP'],
			['C:/Program Files/../PHP', 'C:\PHP'],
			['/home/users/phpstan', '\home\users\phpstan'],
			['/home/users/./phpstan', '\home\users\phpstan'],
			['/home/users/../../phpstan/', '\phpstan'],
			['./phpstan/', 'phpstan'],
			// the path behind a scheme is a URL, so it keeps '/' even on Windows
			['vFs-v1.0://a/b', 'vfs-v1.0://a/b'],
			['phar://C:/php/phpstan.phar/stubs/runtime/Attribute85.php', 'phar://C:/php/phpstan.phar/stubs/runtime/Attribute85.php'],
			['phar://C:\\php\\phpstan.phar\\stubs\\runtime\\Attribute85.php', 'phar://C:/php/phpstan.phar/stubs/runtime/Attribute85.php'],
			// a '..' climbing out of the phar drops the scheme, so the platform separator is back
			['phar://C:/php/phpstan.phar/..', 'C:\php'],
		];
	}

	#[DataProvider('dataNormalizePathOnWindows')]
	public function testNormalizePathOnWindows(string $path, string $normalizedPath): void
	{
		$this->skipIfNotOnWindows();
		$this->assertSame($normalizedPath, self::getContainer()->getByType(FileHelper::class)->normalizePath($path));
	}

	/**
	 * @return string[][]
	 */
	public static function dataNormalizePathOnLinuxOrMac(): array
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

	#[DataProvider('dataNormalizePathOnLinuxOrMac')]
	public function testNormalizePathOnLinuxOrMac(string $path, string $normalizedPath): void
	{
		$this->skipIfNotOnUnix();
		$this->assertSame($normalizedPath, self::getContainer()->getByType(FileHelper::class)->normalizePath($path));
	}

	/**
	 * The path behind a stream-wrapper scheme is a URL: PHP hands out '/'-separated phar:// paths on
	 * every platform, so normalizing must not rewrite it with the platform separator. Covered with an
	 * explicit separator so the Windows behaviour is asserted everywhere.
	 *
	 * @return string[][]
	 */
	public static function dataNormalizePathWithDirectorySeparator(): array
	{
		return [
			['phar://C:/php/phpstan.phar/stubs/runtime/Attribute85.php', '\\', 'phar://C:/php/phpstan.phar/stubs/runtime/Attribute85.php'],
			['phar://C:\\php\\phpstan.phar\\stubs\\runtime\\Attribute85.php', '\\', 'phar://C:/php/phpstan.phar/stubs/runtime/Attribute85.php'],
			['phar:///usr/local/bin/phpstan.phar/stubs/runtime/Attribute85.php', '\\', 'phar:///usr/local/bin/phpstan.phar/stubs/runtime/Attribute85.php'],
			['phar://C:/php/phpstan.phar/conf/../stubs/Foo.php', '\\', 'phar://C:/php/phpstan.phar/stubs/Foo.php'],
			// '..' climbing out of the phar drops the scheme: a plain filesystem path again
			['phar://C:/php/phpstan.phar/..', '\\', 'C:\\php'],
			['C:\\php\\stubs\\Foo.php', '\\', 'C:\\php\\stubs\\Foo.php'],
			['C:/php/stubs/Foo.php', '\\', 'C:\\php\\stubs\\Foo.php'],
		];
	}

	#[DataProvider('dataNormalizePathWithDirectorySeparator')]
	public function testNormalizePathWithDirectorySeparator(string $path, string $directorySeparator, string $normalizedPath): void
	{
		$this->assertSame($normalizedPath, self::getContainer()->getByType(FileHelper::class)->normalizePath($path, $directorySeparator));
	}

}
