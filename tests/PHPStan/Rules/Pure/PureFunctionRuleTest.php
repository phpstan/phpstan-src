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
		]);
	}

}
