<?php declare(strict_types = 1);

namespace PHPStan;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;

/**
 * The before-snapshot always starts with PHPStan's own Composer ClassLoader - bin/phpstan
 * requires its own autoloader long before any project code runs - so every case here models
 * that entry. Which loader the split happens at is the whole point: see
 * https://github.com/phpstan/phpstan/issues/15102
 */
class CollectNewAutoloadFunctionsTest extends TestCase
{

	public function testFalseInputsYieldEmptyResult(): void
	{
		$this->assertSame(
			['prepended' => [], 'appended' => []],
			collectNewAutoloadFunctions(false, false),
		);

		$after = [static function (string $class): void {
		}];
		$this->assertSame(
			['prepended' => [], 'appended' => []],
			collectNewAutoloadFunctions(false, $after),
		);
	}

	public function testAutoloadersAreSplitByTheProjectsComposerPosition(): void
	{
		$phpstanOwn = [new ClassLoader(), 'loadClass'];
		$project = [new ClassLoader(), 'loadClass'];
		$prepended = static function (string $class): void {
		};
		$appended = static function (string $class): void {
		};

		$before = [$phpstanOwn, $project];
		$after = [$phpstanOwn, $prepended, $project, $appended];

		$result = collectNewAutoloadFunctions($before, $after);

		$this->assertSame([$prepended], $result['prepended']);
		$this->assertSame([$appended], $result['appended']);
	}

	/**
	 * PHPStan's own loader must never be the boundary. It is registered before anything
	 * else, so splitting on the first ClassLoader in the queue would classify every
	 * bootstrap-registered autoloader as "after Composer" and consult it only after the
	 * static source locators - the regression this test pins.
	 */
	public function testPhpstansOwnLoaderIsNotTheBoundary(): void
	{
		$phpstanOwn = [new ClassLoader(), 'loadClass'];
		$bootstrap = static function (string $class): void {
		};

		$before = [$phpstanOwn];
		$after = [$phpstanOwn, $bootstrap];

		$result = collectNewAutoloadFunctions($before, $after);

		$this->assertSame([$bootstrap], $result['prepended']);
		$this->assertSame([], $result['appended']);
	}

	/**
	 * The shape typo3/class-alias-loader creates: the bootstrap unregisters
	 * [$composerLoader, 'loadClass'] and registers a wrapper that delegates to it. With the
	 * project's loader gone from the queue there is no boundary left, and the wrapper holds
	 * the priority Composer had, so everything collected is consulted first.
	 */
	public function testProjectLoaderReplacedByABootstrapWrapperIsPrepended(): void
	{
		$phpstanOwn = [new ClassLoader(), 'loadClass'];
		$project = [new ClassLoader(), 'loadClass'];
		$replacement = new class {

			public function loadClass(string $class): void
			{
			}

		};
		$wrapper = [$replacement, 'loadClass'];
		$bootstrap = static function (string $class): void {
		};

		$before = [$phpstanOwn, $project];
		$after = [$phpstanOwn, $wrapper, $bootstrap];

		$result = collectNewAutoloadFunctions($before, $after);

		$this->assertSame([$wrapper, $bootstrap], $result['prepended']);
		$this->assertSame([], $result['appended']);
	}

	public function testComposerAndPharAutoloaderAndPreexistingAreExcluded(): void
	{
		$phpstanOwn = [new ClassLoader(), 'loadClass'];
		$preexisting = static function (string $class): void {
		};
		$project = [new ClassLoader(), 'loadClass'];
		$bootstrap = static function (string $class): void {
		};

		$before = [$phpstanOwn, $preexisting, $project];
		$after = [$phpstanOwn, $preexisting, $project, ['PHPStan\\PharAutoloader', 'loadClass'], $bootstrap];

		$result = collectNewAutoloadFunctions($before, $after);

		$this->assertSame([], $result['prepended']);
		$this->assertSame([$bootstrap], $result['appended']);
	}

	public function testPreexistingAutoloaderBeforeComposerIsNotReported(): void
	{
		$phpstanOwn = [new ClassLoader(), 'loadClass'];
		$preexisting = static function (string $class): void {
		};
		$project = [new ClassLoader(), 'loadClass'];
		$prependedBootstrap = static function (string $class): void {
		};

		// $preexisting was registered before PHPStan loaded the project - it must
		// be ignored even though it sits before Composer in the queue.
		$before = [$phpstanOwn, $preexisting, $project];
		$after = [$phpstanOwn, $preexisting, $prependedBootstrap, $project];

		$result = collectNewAutoloadFunctions($before, $after);

		$this->assertSame([$prependedBootstrap], $result['prepended']);
		$this->assertSame([], $result['appended']);
	}

}
