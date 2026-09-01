<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileReadTrapStreamWrapperTest extends TestCase
{

	/**
	 * @return iterable<string, array{int, bool, string, bool}>
	 */
	public static function dataResolveServesParseError(): iterable
	{
		$pharPath = 'phar:///project/vendor/phpstan/phpstan/phpstan.phar/vendor/composer/../../src/TrinaryLogic.php';
		yield 'phar path, OPcache, PHP 7.4' => [70433, true, $pharPath, true];
		yield 'phar path, OPcache, PHP 8.0' => [80030, true, $pharPath, true];
		yield 'phar path, OPcache, PHP 8.1 invalidates by name' => [80100, true, $pharPath, false];
		yield 'phar path, no OPcache' => [70433, false, $pharPath, false];
		yield 'plain path, OPcache, PHP 7.4' => [70433, true, '/project/src/Foo.php', false];
		yield 'file:// path, OPcache, PHP 7.4' => [70433, true, 'file:///project/src/Foo.php', false];
		yield 'other wrapper, OPcache, PHP 7.4' => [70433, true, 'vfs://project/src/Foo.php', true];
	}

	#[DataProvider('dataResolveServesParseError')]
	public function testResolveServesParseError(int $phpVersionId, bool $opcacheEnabled, string $path, bool $expected): void
	{
		$this->assertSame($expected, FileReadTrapStreamWrapper::resolveServesParseError($phpVersionId, $opcacheEnabled, $path));
	}

}
