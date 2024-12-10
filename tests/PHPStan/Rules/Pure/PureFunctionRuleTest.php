<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PureFunctionRule>
 */
class PureFunctionRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new PureFunctionRule(new FunctionPurityCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/pure-function.php'], [
			[
				'Function PureFunction\doFoo() is marked as pure but parameter $p is passed by reference.',
				8,
			],
			[
				'Impure echo in pure function PureFunction\doFoo().',
				10,
			],
			[
				'Function PureFunction\doFoo2() is marked as pure but returns void.',
				16,
			],
			[
				'Impure exit in pure function PureFunction\doFoo2().',
				18,
			],
			[
				'Impure property assignment in pure function PureFunction\doFoo3().',
				26,
			],
			[
				'Possibly impure call to a callable in pure function PureFunction\testThese().',
				60,
			],
			[
				'Possibly impure call to a callable in pure function PureFunction\testThese().',
				61,
			],
			[
				'Impure call to function PureFunction\impureFunction() in pure function PureFunction\testThese().',
				63,
			],
			[
				'Impure call to function PureFunction\voidFunction() in pure function PureFunction\testThese().',
				64,
			],
			[
				'Possibly impure call to function PureFunction\possiblyImpureFunction() in pure function PureFunction\testThese().',
				65,
			],
			[
				'Possibly impure call to unknown function in pure function PureFunction\testThese().',
				66,
			],
			[
				'Function PureFunction\actuallyPure() is marked as impure but does not have any side effects.',
				72,
			],
			[
				'Function PureFunction\emptyVoidFunction() returns void but does not have any side effects.',
				84,
			],
			[
				'Impure access to superglobal variable in pure function PureFunction\pureButAccessSuperGlobal().',
				102,
			],
			[
				'Impure access to superglobal variable in pure function PureFunction\pureButAccessSuperGlobal().',
				103,
			],
			[
				'Impure access to superglobal variable in pure function PureFunction\pureButAccessSuperGlobal().',
				105,
			],
			[
				'Impure global variable in pure function PureFunction\functionWithGlobal().',
				118,
			],
			[
				'Impure static variable in pure function PureFunction\functionWithStaticVariable().',
				128,
			],
			[
				'Possibly impure call to a Closure in pure function PureFunction\callsClosures().',
				139,
			],
			[
				'Possibly impure call to a Closure in pure function PureFunction\callsClosures().',
				140,
			],
			[
				'Impure output between PHP opening and closing tags in pure function PureFunction\justContainsInlineHtml().',
				160,
			],
		]);
	}

	public function testFirstClassCallable(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/first-class-callable-pure-function.php'], [
			[
				'Impure call to method FirstClassCallablePureFunction\Foo::impureFunction() in pure function FirstClassCallablePureFunction\testThese().',
				61,
			],
			[
				'Impure call to method FirstClassCallablePureFunction\Foo::voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				64,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\impureFunction() in pure function FirstClassCallablePureFunction\testThese().',
				70,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				73,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				75,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\impureFunction() in pure function FirstClassCallablePureFunction\testThese().',
				81,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				84,
			],
			[
				'Impure call to method FirstClassCallablePureFunction\Foo::impureFunction() in pure function FirstClassCallablePureFunction\testThese().',
				90,
			],
			[
				'Impure call to method FirstClassCallablePureFunction\Foo::voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				93,
			],
			[
				'Possibly impure call to a callable in pure function FirstClassCallablePureFunction\callCallbackImmediately().',
				102,
			],
		]);
	}

	public function testBug11361(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11361-pure.php'], [
			[
				'Impure call to a Closure with by-ref parameter in pure function Bug11361Pure\foo().',
				14,
			],
		]);
	}

	public function testBug12224(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12224.php'], [
			[
				'Function PHPStan\Rules\Pure\data\pureWithThrowsVoid() is marked as pure but returns void.',
				18,
			],
		]);
	}

}
