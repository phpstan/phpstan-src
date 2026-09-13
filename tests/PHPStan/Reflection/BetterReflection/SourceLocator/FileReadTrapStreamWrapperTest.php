<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use function escapeshellarg;
use function exec;
use function explode;
use function extension_loaded;
use function implode;
use function sprintf;
use function str_contains;
use const PHP_BINARY;

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

	/**
	 * Probing a name whose PSR-4 prefix resolves to a file the process already
	 * ran must not run it again: with OPcache holding the script, an include is
	 * served from the cache without the trap being asked for the contents. A name
	 * whose file nothing has loaded must still resolve, without executing it.
	 *
	 * Needs its own process: OPcache is only on in the processes PHPStan spawns
	 * for itself, and the failure is a fatal error.
	 */
	#[Group('exec')]
	public function testTrapSurvivesOpcacheCacheHit(): void
	{
		if (!extension_loaded('Zend OPcache')) {
			self::markTestSkipped('OPcache is not available.');
		}

		exec(sprintf(
			'%s -d opcache.enable=1 -d opcache.enable_cli=1 -d opcache.validate_timestamps=0 %s 2>&1',
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/data/opcache-trap/driver.php'),
		), $outputLines, $exitCode);
		$output = implode("\n", $outputLines);

		$this->assertSame(0, $exitCode, $output);

		$values = [];
		foreach ($outputLines as $outputLine) {
			if (!str_contains($outputLine, '=')) {
				continue;
			}
			[$name, $value] = explode('=', $outputLine, 2);
			$values[$name] = $value;
		}

		// hold whether or not OPcache could be turned on
		$this->assertSame('1', $values['survivedLoadedProbe'] ?? null, $output);
		$this->assertSame('1', $values['resolvedCold'] ?? null, $output);
		$this->assertSame('1', $values['coldFileNotExecuted'] ?? null, $output);

		if (($values['opcacheEnabled'] ?? '0') === '1') {
			return;
		}

		self::markTestSkipped('OPcache could not be enabled for the CLI, so the cache hit was not exercised.');
	}

}
