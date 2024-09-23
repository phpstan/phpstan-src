<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingMethodSelfOutTypeRule>
 */
class MissingMethodSelfOutTypeRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new MissingMethodSelfOutTypeRule(new MissingTypehintCheck(true, true, []));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-method-self-out-type.php'], [
			[
				'Method MissingMethodSelfOutType\Foo::doFoo() has PHPDoc tag @phpstan-self-out with no value type specified in iterable type array.',
				14,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
			[
				'Method MissingMethodSelfOutType\Foo::doFoo2() has PHPDoc tag @phpstan-self-out with generic class MissingMethodSelfOutType\Foo but does not specify its types: T',
				22,
			],
			[
				'Method MissingMethodSelfOutType\Foo::doFoo3() has PHPDoc tag @phpstan-self-out with no signature specified for callable.',
				30,
			],
		]);
	}

}
