<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PureAbstractMethodRule>
 */
class PureAbstractMethodRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new PureAbstractMethodRule(new FunctionPurityCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/pure-abstract-method.php'], [
			[
				'Method PureAbstractMethod\Foo::pureByRef() is marked as pure but parameter $p is passed by reference.',
				11,
			],
			[
				'Method PureAbstractMethod\Foo::pureVoid() is marked as pure but returns void.',
				16,
			],
			[
				'Method PureAbstractMethod\Foo::requiredCount() is marked @pure-unless-parameter-passed for parameter $count, but $count is not optional, so method PureAbstractMethod\Foo::requiredCount() is never pure.',
				33,
			],
			[
				'Method PureAbstractMethod\Foo::byValueFlag() is marked @pure-unless-parameter-passed for parameter $flag, but $flag is not passed by reference.',
				38,
			],
			[
				'Method PureAbstractMethod\Foo::alreadyPureCallback() is marked @pure-unless-callable-is-impure for parameter $cb, but $cb is already a pure callable, so method PureAbstractMethod\Foo::alreadyPureCallback() can be marked @phpstan-pure instead.',
				50,
			],
			[
				'Method PureAbstractMethod\Bar::pureByRef() is marked as pure but parameter $p is passed by reference.',
				66,
			],
			[
				'Method PureAbstractMethod\Bar::requiredCount() is marked @pure-unless-parameter-passed for parameter $count, but $count is not optional, so method PureAbstractMethod\Bar::requiredCount() is never pure.',
				72,
			],
		]);
	}

}
