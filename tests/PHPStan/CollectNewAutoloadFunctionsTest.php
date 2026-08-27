<?php declare(strict_types = 1);

namespace PHPStan;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;

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

	public function testAutoloadersAreSplitByComposerPosition(): void
	{
		$prepended = static function (string $class): void {
		};
		$composer = new ClassLoader();
		$appended = static function (string $class): void {
		};

		$before = [];
		$after = [$prepended, [$composer, 'loadClass'], $appended];

		$result = collectNewAutoloadFunctions($before, $after);

		$this->assertSame([$prepended], $result['prepended']);
		$this->assertSame([$appended], $result['appended']);
	}

	public function testWithoutComposerEverythingIsAppended(): void
	{
		$first = static function (string $class): void {
		};
		$second = static function (string $class): void {
		};

		$result = collectNewAutoloadFunctions([], [$first, $second]);

		$this->assertSame([], $result['prepended']);
		$this->assertSame([$first, $second], $result['appended']);
	}

	public function testComposerAndPharAutoloaderAndPreexistingAreExcluded(): void
	{
		$preexisting = static function (string $class): void {
		};
		$composer = new ClassLoader();
		$bootstrap = static function (string $class): void {
		};

		$before = [$preexisting];
		$after = [$preexisting, [$composer, 'loadClass'], ['PHPStan\\PharAutoloader', 'loadClass'], $bootstrap];

		$result = collectNewAutoloadFunctions($before, $after);

		$this->assertSame([], $result['prepended']);
		$this->assertSame([$bootstrap], $result['appended']);
	}

	public function testPreexistingAutoloaderBeforeComposerIsNotReported(): void
	{
		$preexisting = static function (string $class): void {
		};
		$composer = new ClassLoader();
		$prependedBootstrap = static function (string $class): void {
		};

		// $preexisting was registered before PHPStan loaded the project - it must
		// be ignored even though it sits before Composer in the queue.
		$before = [$preexisting];
		$after = [$preexisting, $prependedBootstrap, [$composer, 'loadClass']];

		$result = collectNewAutoloadFunctions($before, $after);

		$this->assertSame([$prependedBootstrap], $result['prepended']);
		$this->assertSame([], $result['appended']);
	}

}
