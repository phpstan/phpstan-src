<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_fill_keys;
use function array_keys;
use function chmod;
use function fileowner;
use function is_dir;
use function is_link;
use function mkdir;
use function rmdir;
use function scandir;
use function symlink;
use function sys_get_temp_dir;
use function time;
use function touch;
use function uniqid;
use function unlink;

final class TurboProcessRestarterTest extends PHPStanTestCase
{

	private const STOCK_ARGS = [
		'opcache.enable=1',
		'opcache.enable_cli=1',
		'opcache.jit=disable',
		'opcache.jit_buffer_size=0',
		'opcache.validate_timestamps=0',
		'opcache.file_update_protection=0',
		'opcache.max_file_size=0',
		'opcache.file_cache=',
		'opcache.save_comments=1',
		'opcache.optimization_level=0x7FFEBFFF',
		'opcache.memory_consumption=256',
		'opcache.interned_strings_buffer=64',
		'opcache.max_accelerated_files=20000',
	];

	/**
	 * @return iterable<string, array{array<string, string|false>, list<string>}>
	 */
	public static function dataResolveOpcacheArgs(): iterable
	{
		$stock = [
			'opcache.file_cache_only' => '0',
			'opcache.preload' => '',
			'opcache.memory_consumption' => '128',
			'opcache.interned_strings_buffer' => '8',
			'opcache.max_accelerated_files' => '10000',
		];

		yield 'stock php.ini' => [$stock, self::STOCK_ARGS];

		yield 'directives unknown (ini_get false)' => [
			array_fill_keys(array_keys($stock), false),
			self::STOCK_ARGS,
		];

		// a web-tuned file cache must not persist this run's opcodes: after a
		// PHPStan update it would serve the old ones (nothing but the mtime is
		// validated, and validation is off here) — the same arguments blank it
		yield 'file cache configured' => [
			['opcache.file_cache' => '/tmp/opcache'] + $stock,
			self::STOCK_ARGS,
		];

		// blanking the file cache would be a fatal startup error here, and SHM
		// may be unavailable on purpose — leave the configuration alone
		yield 'file_cache_only' => [
			['opcache.file_cache_only' => '1'] + $stock,
			[],
		];

		// would execute the application's preload script inside PHPStan, and
		// cannot be blanked with -d (OnUpdateStringUnempty rejects '')
		yield 'preload configured' => [
			['opcache.preload' => '/var/www/config/preload.php'] + $stock,
			[],
		];

		yield 'larger user sizes are kept' => [
			['opcache.memory_consumption' => '512', 'opcache.interned_strings_buffer' => '96', 'opcache.max_accelerated_files' => '100000'] + $stock,
			self::withSizes(512, 96, 100000),
		];

		yield 'smaller user sizes are raised' => [
			['opcache.memory_consumption' => '64', 'opcache.interned_strings_buffer' => '4', 'opcache.max_accelerated_files' => '2000'] + $stock,
			self::STOCK_ARGS,
		];

		// the interned strings buffer is carved out of memory_consumption; an
		// interned buffer at or above it is a fatal startup error
		yield 'user interned buffer larger than the memory' => [
			['opcache.memory_consumption' => '128', 'opcache.interned_strings_buffer' => '300'] + $stock,
			self::withSizes(492, 300, 20000),
		];
	}

	/**
	 * @param array<string, string|false> $ini
	 * @param list<string> $expected
	 */
	#[DataProvider('dataResolveOpcacheArgs')]
	public function testResolveOpcacheArgs(array $ini, array $expected): void
	{
		$this->assertSame($expected, TurboProcessRestarter::resolveOpcacheArgs($ini));
	}

	public function testResolveOpcacheArgsWithFileCache(): void
	{
		$ini = [
			'opcache.file_cache_only' => '0',
			'opcache.preload' => '',
			'opcache.memory_consumption' => '128',
			'opcache.interned_strings_buffer' => '8',
			'opcache.max_accelerated_files' => '10000',
		];

		// a cache that outlives the process validates timestamps, at PHP's
		// defaults, so neither a PHPStan update nor an edited bootstrap file
		// is served from it
		$expected = self::STOCK_ARGS;
		$expected[4] = 'opcache.validate_timestamps=1';
		$expected[5] = 'opcache.file_update_protection=2';
		$expected[7] = 'opcache.file_cache=/tmp/phpstan-opcache-501/0123456789abcdef';

		$this->assertSame($expected, TurboProcessRestarter::resolveOpcacheArgs($ini, '/tmp/phpstan-opcache-501/0123456789abcdef'));

		// the configurations left alone stay alone
		$this->assertSame([], TurboProcessRestarter::resolveOpcacheArgs(['opcache.file_cache_only' => '1'] + $ini, '/tmp/phpstan-opcache-501/0123456789abcdef'));
	}

	public function testResolveFileCacheKey(): void
	{
		$key = TurboProcessRestarter::resolveFileCacheKey('ABC123', 'binary:/vendor/phpstan/phpstan/turbo-ext/linux-gnu-x86_64/phpstan_turbo-8.5.so:1:2', false);

		$this->assertMatchesRegularExpression('~^[0-9a-f]{16}$~', $key);
		$this->assertSame($key, TurboProcessRestarter::resolveFileCacheKey('ABC123', 'binary:/vendor/phpstan/phpstan/turbo-ext/linux-gnu-x86_64/phpstan_turbo-8.5.so:1:2', false));

		// another PHPStan build, another extension binary, and --debug (which
		// keeps the type checks the extension's pass drops) each compile other
		// opcodes, so each gets its own directory
		$this->assertNotSame($key, TurboProcessRestarter::resolveFileCacheKey('DEF456', 'binary:/vendor/phpstan/phpstan/turbo-ext/linux-gnu-x86_64/phpstan_turbo-8.5.so:1:2', false));
		$this->assertNotSame($key, TurboProcessRestarter::resolveFileCacheKey('ABC123', 'none', false));
		$this->assertNotSame($key, TurboProcessRestarter::resolveFileCacheKey('ABC123', 'binary:/vendor/phpstan/phpstan/turbo-ext/linux-gnu-x86_64/phpstan_turbo-8.5.so:1:2', true));
	}

	/**
	 * @return iterable<string, array{array<string, string>, bool}>
	 */
	public static function dataResolveContinuousIntegration(): iterable
	{
		yield 'no CI variables' => [['HOME' => '/home/user', 'PATH' => '/usr/bin'], false];
		yield 'GitHub Actions' => [['CI' => 'true', 'GITHUB_ACTIONS' => 'true'], true];
		yield 'GitLab' => [['CI' => 'true', 'GITLAB_CI' => 'true'], true];
		yield 'Jenkins sets no CI variable' => [['JENKINS_URL' => 'https://jenkins.example.com/'], true];
		yield 'Azure Pipelines' => [['TF_BUILD' => 'True'], true];
		yield 'CI switched off' => [['CI' => 'false'], false];
		yield 'CI empty' => [['CI' => ''], false];
	}

	/**
	 * @param array<string, string> $environment
	 */
	#[DataProvider('dataResolveContinuousIntegration')]
	public function testResolveContinuousIntegration(array $environment, bool $expected): void
	{
		$this->assertSame($expected, TurboProcessRestarter::resolveContinuousIntegration($environment));
	}

	public function testIsPrivateDirectory(): void
	{
		$base = self::createTemporaryDirectory();
		$userId = (int) fileowner($base);

		mkdir($base . '/private', 0700);
		$this->assertTrue(TurboProcessRestarter::isPrivateDirectory($base . '/private', $userId));

		// opcodes in a directory someone else can write to could be planted
		mkdir($base . '/shared');
		chmod($base . '/shared', 0777);
		$this->assertFalse(TurboProcessRestarter::isPrivateDirectory($base . '/shared', $userId));

		symlink($base . '/private', $base . '/link');
		$this->assertFalse(TurboProcessRestarter::isPrivateDirectory($base . '/link', $userId));

		$this->assertFalse(TurboProcessRestarter::isPrivateDirectory($base . '/private', $userId + 1));
		$this->assertFalse(TurboProcessRestarter::isPrivateDirectory($base . '/missing', $userId));

		self::removeDirectory($base);
	}

	public function testPruneFileCacheDirectories(): void
	{
		$base = self::createTemporaryDirectory();
		$now = time();
		foreach (['current' => 30, 'stale' => 8, 'recent' => 2] as $name => $daysAgo) {
			mkdir($base . '/' . $name . '/nested', 0700, true);
			touch($base . '/' . $name . '/nested/script.php.bin');
			touch($base . '/' . $name, $now - $daysAgo * 24 * 60 * 60);
		}
		$outside = self::createTemporaryDirectory();
		touch($outside . '/keep.bin');
		symlink($outside, $base . '/link');

		TurboProcessRestarter::pruneFileCacheDirectories($base, 'current', $now);

		// the key in use survives however old, a stale sibling goes, and a
		// symlink is neither followed nor removed
		$this->assertDirectoryExists($base . '/current/nested');
		$this->assertDirectoryDoesNotExist($base . '/stale');
		$this->assertDirectoryExists($base . '/recent');
		$this->assertFileExists($outside . '/keep.bin');
		$this->assertTrue(is_link($base . '/link'));

		self::removeDirectory($base);
		self::removeDirectory($outside);
	}

	private static function createTemporaryDirectory(): string
	{
		$directory = sys_get_temp_dir() . '/' . uniqid('phpstan-file-cache-test-', true);
		mkdir($directory, 0700);

		return $directory;
	}

	private static function removeDirectory(string $directory): void
	{
		foreach (scandir($directory) as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$path = $directory . '/' . $entry;
			if (is_link($path) || !is_dir($path)) {
				unlink($path);
			} else {
				self::removeDirectory($path);
			}
		}
		rmdir($directory);
	}

	/**
	 * @return list<string>
	 */
	private static function withSizes(int $memory, int $interned, int $files): array
	{
		$args = self::STOCK_ARGS;
		$args[10] = 'opcache.memory_consumption=' . $memory;
		$args[11] = 'opcache.interned_strings_buffer=' . $interned;
		$args[12] = 'opcache.max_accelerated_files=' . $files;

		return $args;
	}

	/**
	 * @return iterable<string, array{array<string, string|false>, bool}>
	 */
	public static function dataResolveOpcacheRestartNeeded(): iterable
	{
		// what the restarted process reads back through ini_get() for the stock args
		$inEffect = [
			'opcache.enable' => '1',
			'opcache.enable_cli' => '1',
			'opcache.jit' => 'disable',
			'opcache.jit_buffer_size' => '0',
			'opcache.validate_timestamps' => '0',
			'opcache.file_update_protection' => '0',
			'opcache.max_file_size' => '0',
			'opcache.file_cache' => '',
			'opcache.save_comments' => '1',
			'opcache.optimization_level' => '0x7FFEBFFF',
			'opcache.memory_consumption' => '256',
			'opcache.interned_strings_buffer' => '64',
			'opcache.max_accelerated_files' => '20000',
		];

		yield 'already in effect' => [$inEffect, false];

		// the optimizer switched off in php.ini would also switch off the
		// extension's trusted-types pass, which runs inside it
		yield 'optimizer disabled' => [
			['opcache.optimization_level' => '0'] + $inEffect,
			true,
		];

		// php.ini spellings the ini parser leaves as-is versus its "1"/"" for booleans
		yield 'already in effect, php.ini spellings' => [
			['opcache.enable' => 'On', 'opcache.validate_timestamps' => '', 'opcache.jit' => 'Disable'] + $inEffect,
			false,
		];

		yield 'stock CLI: OPcache dormant' => [
			['opcache.enable_cli' => '', 'opcache.validate_timestamps' => '1', 'opcache.file_update_protection' => '2', 'opcache.memory_consumption' => '128', 'opcache.interned_strings_buffer' => '8', 'opcache.max_accelerated_files' => '10000'] + $inEffect,
			true,
		];

		yield 'OPcache active but JIT on' => [
			['opcache.jit' => 'tracing', 'opcache.jit_buffer_size' => '64M'] + $inEffect,
			true,
		];

		yield 'OPcache active with a file cache' => [
			['opcache.file_cache' => '/tmp/opcache'] + $inEffect,
			true,
		];

		// PHP < 8.0 has no JIT directives — nothing a restart could change there
		yield 'directive unknown on this PHP' => [
			['opcache.jit' => false, 'opcache.jit_buffer_size' => false] + $inEffect,
			false,
		];
	}

	/**
	 * @param array<string, string|false> $currentIniValues
	 */
	#[DataProvider('dataResolveOpcacheRestartNeeded')]
	public function testResolveOpcacheRestartNeeded(array $currentIniValues, bool $expected): void
	{
		$this->assertSame($expected, TurboProcessRestarter::resolveOpcacheRestartNeeded(self::STOCK_ARGS, $currentIniValues));
	}

}
