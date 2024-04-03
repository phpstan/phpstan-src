<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

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
				'Impure call to function PureFunction\impureFunction() in pure function PureFunction\testThese().',
				62,
			],
			[
				'Impure call to function PureFunction\voidFunction() in pure function PureFunction\testThese().',
				63,
			],
			[
				'Possibly impure call to function PureFunction\possiblyImpureFunction() in pure function PureFunction\testThese().',
				64,
			],
			[
				'Possibly impure call to unknown function in pure function PureFunction\testThese().',
				65,
			],
			[
				'Function PureFunction\actuallyPure() is marked as impure but does not have any side effects.',
				71,
			],
			[
				'Function PureFunction\emptyVoidFunction() returns void but does not have any side effects.',
				83,
			],
			[
				'Impure access to superglobal variable in pure function PureFunction\pureButAccessSuperGlobal().',
				101,
			],
			[
				'Impure access to superglobal variable in pure function PureFunction\pureButAccessSuperGlobal().',
				102,
			],
			[
				'Impure access to superglobal variable in pure function PureFunction\pureButAccessSuperGlobal().',
				104,
			],
			[
				'Impure global variable in pure function PureFunction\functionWithGlobal().',
				117,
			],
			[
				'Impure static variable in pure function PureFunction\functionWithStaticVariable().',
				127,
			],
		]);
	}

}
