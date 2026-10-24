<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

use PHPStan\Turbo\TurboExtensionSelector;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_map;
use function extension_loaded;

#[CoversNothing]
class ExtensionRegistryTest extends TestCase
{

	private const TURBO_BINARY = '/tmp/turbo-ext/linux-gnu-x86_64/phpstan_turbo-8.2.so';

	/**
	 * @return iterable<string, array{list<string>, array<string, string>, string|null, bool, list<string>}>
	 */
	public static function dataCollectReasonsFor(): iterable
	{
		yield 'a clean process has nothing to restart for' => [
			['Core', 'json'],
			[],
			null,
			false,
			[],
		];

		yield 'armed xdebug is disarmed' => [
			['Core', 'xdebug'],
			['xdebug.mode' => 'develop,debug'],
			null,
			false,
			['xdebug'],
		];

		yield 'xdebug.mode=off measures as absent, so it is left alone' => [
			['Core', 'xdebug'],
			['xdebug.mode' => 'off'],
			null,
			false,
			[],
		];

		yield 'an empty xdebug.mode list is off' => [
			['Core', 'xdebug'],
			['xdebug.mode' => ' , '],
			null,
			false,
			[],
		];

		yield 'xdebug without a mode setting predates modes and is always armed' => [
			['Core', 'xdebug'],
			[],
			null,
			false,
			['xdebug'],
		];

		yield '--xdebug keeps xdebug' => [
			['Core', 'xdebug'],
			['xdebug.mode' => 'debug'],
			null,
			true,
			[],
		];

		yield 'armed pcov is disarmed' => [
			['Core', 'pcov'],
			['pcov.enabled' => '1'],
			null,
			false,
			['pcov'],
		];

		yield 'pcov that is already disarmed is left alone' => [
			['Core', 'pcov'],
			['pcov.enabled' => '0'],
			null,
			false,
			[],
		];

		yield 'the pcov ini setting without the extension is not a reason' => [
			['Core'],
			['pcov.enabled' => '1'],
			null,
			false,
			[],
		];

		yield 'uopz rewrites opcodes unless disabled' => [
			['Core', 'uopz'],
			['uopz.disable' => '0'],
			null,
			false,
			['uopz'],
		];

		yield 'uopz.disable=1 is a working off switch' => [
			['Core', 'uopz'],
			['uopz.disable' => '1'],
			null,
			false,
			[],
		];

		yield 'the turbo binary is loaded when it is not loaded yet' => [
			['Core'],
			[],
			self::TURBO_BINARY,
			false,
			['phpstan_turbo'],
		];

		yield 'an already loaded turbo extension is not a reason' => [
			['Core', 'phpstan_turbo'],
			[],
			self::TURBO_BINARY,
			false,
			[],
		];

		yield 'tideways is stripped when the turbo binary is about to be loaded' => [
			['Core', 'tideways'],
			[],
			self::TURBO_BINARY,
			false,
			['phpstan_turbo', 'tideways'],
		];

		yield 'tideways is stripped when turbo is already loaded' => [
			['Core', 'tideways', 'phpstan_turbo'],
			[],
			null,
			false,
			['tideways'],
		];

		yield 'tideways without turbo is left alone' => [
			['Core', 'tideways'],
			[],
			null,
			false,
			[],
		];

		yield 'one restart handles every armed hook together' => [
			['Core', 'xdebug', 'pcov', 'uopz'],
			['xdebug.mode' => 'debug', 'pcov.enabled' => '1', 'uopz.disable' => '0'],
			null,
			false,
			['xdebug', 'pcov', 'uopz'],
		];

		yield '--xdebug still restarts for the other reasons' => [
			['Core', 'xdebug', 'pcov'],
			['xdebug.mode' => 'debug', 'pcov.enabled' => '1'],
			self::TURBO_BINARY,
			true,
			['pcov', 'phpstan_turbo'],
		];
	}

	/**
	 * @param list<string> $loadedExtensions
	 * @param array<string, string> $iniSettings
	 * @param list<string> $expectedExtensions
	 */
	#[DataProvider('dataCollectReasonsFor')]
	public function testCollectReasonsFor(
		array $loadedExtensions,
		array $iniSettings,
		?string $turboExtensionPath,
		bool $allowXdebug,
		array $expectedExtensions,
	): void
	{
		$reasons = RestartExtensionRegistry::collectReasonsFor($loadedExtensions, $iniSettings, $turboExtensionPath, $allowXdebug);

		$this->assertSame(
			$expectedExtensions,
			array_map(static fn (RestartReason $reason): string => $reason->getExtension(), $reasons),
		);
	}

	public function testXdebugIsFlaggedRatherThanStripped(): void
	{
		// stripping would take xdebug_*() out of reflection and so change what
		// PHPStan knows about code calling them; the flag measures the same
		$reasons = RestartExtensionRegistry::collectReasonsFor(['xdebug'], ['xdebug.mode' => 'debug'], null, false);

		$this->assertCount(1, $reasons);
		$this->assertSame([], $reasons[0]->getStrippedExtensions());
		$this->assertSame(['xdebug.mode' => 'off'], $reasons[0]->getIniSettings());
		// XDEBUG_MODE wins over the ini, so the ini alone would be overridable
		$this->assertSame(['XDEBUG_MODE' => 'off'], $reasons[0]->getEnvironmentVariables());
		$this->assertSame([], $reasons[0]->getCommandLineArguments());
	}

	public function testPcovIsFlaggedRatherThanStripped(): void
	{
		$reasons = RestartExtensionRegistry::collectReasonsFor(['pcov'], ['pcov.enabled' => '1'], null, false);

		$this->assertCount(1, $reasons);
		$this->assertSame([], $reasons[0]->getStrippedExtensions());
		$this->assertSame(['pcov.enabled' => '0'], $reasons[0]->getIniSettings());
		$this->assertSame([], $reasons[0]->getEnvironmentVariables());
		$this->assertSame([], $reasons[0]->getCommandLineArguments());
	}

	public function testTidewaysIsStrippedRatherThanFlagged(): void
	{
		$reasons = RestartExtensionRegistry::collectReasonsFor(['tideways', 'phpstan_turbo'], [], null, false);

		$this->assertCount(1, $reasons);
		$this->assertSame(['tideways'], $reasons[0]->getStrippedExtensions());
		$this->assertSame([], $reasons[0]->getIniSettings());
		$this->assertSame([], $reasons[0]->getEnvironmentVariables());
		$this->assertSame([], $reasons[0]->getCommandLineArguments());
	}

	public function testTurboIsAppendedToTheCommandLineTogetherWithItsMarker(): void
	{
		$reasons = RestartExtensionRegistry::collectReasonsFor([], [], self::TURBO_BINARY, false);

		$this->assertCount(1, $reasons);
		$this->assertSame([], $reasons[0]->getStrippedExtensions());
		$this->assertSame([], $reasons[0]->getIniSettings());
		$this->assertSame([
			'-d',
			'extension=' . self::TURBO_BINARY,
			'-d',
			TurboExtensionSelector::EXTENSION_PATH_INI . '=' . self::TURBO_BINARY,
		], $reasons[0]->getCommandLineArguments());
	}

	public function testOpcacheIsActivatedWhenDormant(): void
	{
		$entries = ['opcache.enable_cli=1', 'opcache.jit=disable', 'opcache.file_cache='];
		$reasons = RestartExtensionRegistry::collectReasonsFor([], [], null, false, $entries, [
			'opcache.enable_cli' => '',
			'opcache.jit' => 'disable',
			'opcache.file_cache' => '',
		]);

		$this->assertCount(1, $reasons);
		$this->assertSame('opcache', $reasons[0]->getExtension());
		$this->assertSame([
			'opcache.enable_cli' => '1',
			'opcache.jit' => 'disable',
			'opcache.file_cache' => '',
		], $reasons[0]->getIniSettings());
	}

	public function testOpcacheAlreadyConfiguredIsNotAReason(): void
	{
		$entries = ['opcache.enable_cli=1', 'opcache.jit=disable'];

		$this->assertSame([], RestartExtensionRegistry::collectReasonsFor([], [], null, false, $entries, [
			'opcache.enable_cli' => '1',
			'opcache.jit' => 'disable',
		]));
		// no entries means OPcache is to be left alone - not loaded, preloading, or not fork-capable
		$this->assertSame([], RestartExtensionRegistry::collectReasonsFor([], [], null, false, [], []));
	}

	public function testCollectReasonsDescribesTheCurrentProcess(): void
	{
		// the test suite runs with coverage off and without the turbo binary of
		// a phar installation, so this only asserts that the real-process entry
		// point agrees with what the process actually has loaded
		foreach (RestartExtensionRegistry::collectReasons(false) as $reason) {
			$this->assertContains($reason->getExtension(), ['xdebug', 'pcov', 'uopz', 'phpstan_turbo', 'tideways', 'opcache']);
		}

		$this->assertSame([], RestartExtensionRegistry::collectReasonsFor([], [], null, false));
	}

	public function testIsXdebugActiveRequiresTheExtension(): void
	{
		if (extension_loaded('xdebug')) {
			$this->markTestSkipped('xdebug is loaded in this process.');
		}

		$this->assertFalse(RestartExtensionRegistry::isXdebugActive());
		foreach (RestartExtensionRegistry::describeDetectedExtensions() as $description) {
			$this->assertStringNotContainsString('xdebug', $description);
		}
	}

}
