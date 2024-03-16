<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PureMethodRule>
 */
class PureMethodRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new PureMethodRule(new FunctionPurityCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/pure-method.php'], [
			[
				'Method PureMethod\Foo::doFoo() is marked as pure but parameter $p is passed by reference.',
				11,
			],
			[
				'Impure echo in pure method PureMethod\Foo::doFoo().',
				13,
			],
			[
				'Method PureMethod\Foo::doFoo2() is marked as pure but returns void.',
				19,
			],
			[
				'Impure die in pure method PureMethod\Foo::doFoo2().',
				21,
			],
			[
				'Impure property assignment in pure method PureMethod\Foo::doFoo3().',
				29,
			],
		]);
	}

}
