<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_map;
use function escapeshellarg;
use function exec;
use function implode;
use function json_decode;
use const PHP_BINARY;

final class InheritedPhpConfigTest extends TestCase
{

	/**
	 * @return iterable<string, array{string|false, string|false, string|false, list<string>}>
	 */
	public static function dataResolveArgs(): iterable
	{
		yield 'php.ini and a scan directory' => [
			'/etc/php/php.ini',
			'/etc/php/conf.d/10-opcache.ini,/etc/php/conf.d/20-xdebug.ini',
			false,
			['-c', '/etc/php/php.ini', '-d', "sys_temp_dir='/tmp'"],
		];
		yield 'no scanned ini files' => [
			'/etc/php/php.ini',
			false,
			false,
			['-n', '-c', '/etc/php/php.ini', '-d', "sys_temp_dir='/tmp'"],
		];
		yield 'an empty scan directory' => [
			'/etc/php/php.ini',
			"\n",
			false,
			['-n', '-c', '/etc/php/php.ini', '-d', "sys_temp_dir='/tmp'"],
		];
		yield 'no php.ini at all' => [
			false,
			false,
			false,
			['-n', '-d', "sys_temp_dir='/tmp'"],
		];
		yield 'an empty php.ini path' => [
			'',
			'/etc/php/conf.d/20-xdebug.ini',
			false,
			['-d', "sys_temp_dir='/tmp'"],
		];
		yield 'Xdebug turned off on the command line' => [
			'/etc/php/php.ini',
			'/etc/php/conf.d/20-xdebug.ini',
			// what the ini parser makes of -d xdebug.mode=off
			'',
			['-c', '/etc/php/php.ini', '-d', "sys_temp_dir='/tmp'", '-d', 'xdebug.mode='],
		];
		yield 'Xdebug left on' => [
			'/etc/php/php.ini',
			'/etc/php/conf.d/20-xdebug.ini',
			'debug,develop',
			['-c', '/etc/php/php.ini', '-d', "sys_temp_dir='/tmp'", '-d', 'xdebug.mode=debug,develop'],
		];
		yield 'Xdebug turned off with no ini file to inherit' => [
			false,
			false,
			'',
			['-n', '-d', "sys_temp_dir='/tmp'", '-d', 'xdebug.mode='],
		];
	}

	/**
	 * @param string|false $loadedIniFile
	 * @param string|false $scannedIniFiles
	 * @param string|false $xdebugMode
	 * @param list<string> $expected
	 */
	#[DataProvider('dataResolveArgs')]
	public function testResolveArgs($loadedIniFile, $scannedIniFiles, $xdebugMode, array $expected): void
	{
		$this->assertSame($expected, InheritedPhpConfig::resolveArgs($loadedIniFile, $scannedIniFiles, '/tmp', $xdebugMode));
	}

	/**
	 * @return iterable<string, array{list<string>}>
	 */
	public static function dataChildProcessRepeatsThePhpConfigurationOfItsParent(): iterable
	{
		yield 'as started' => [[]];
		// https://github.com/phpstan/phpstan/issues/15189 - Xdebug stayed
		// enabled in the child processes because -d entries are not inherited
		yield 'with Xdebug turned off' => [['-d', 'xdebug.mode=off']];
		yield 'without the ini scan directory' => [['-n']];
	}

	/**
	 * @param list<string> $phpOptions
	 */
	#[DataProvider('dataChildProcessRepeatsThePhpConfigurationOfItsParent')]
	public function testChildProcessRepeatsThePhpConfigurationOfItsParent(array $phpOptions): void
	{
		$command = implode(' ', array_map(
			static fn (string $arg): string => escapeshellarg($arg),
			[PHP_BINARY, ...$phpOptions, __DIR__ . '/data/print-php-config.php'],
		));
		exec($command, $outputLines, $exitCode);
		$this->assertSame(0, $exitCode, implode("\n", $outputLines));

		$result = json_decode(implode('', $outputLines), true);
		$this->assertIsArray($result);
		$this->assertSame($result['parent'], $result['child']);
	}

}
