<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<SetPropertyHookParameterRule>
 */
class SetPropertyHookParameterRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new SetPropertyHookParameterRule(new MissingTypehintCheck(true, []), true, true);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/set-property-hook-parameter.php'], [
			[
				'Parameter $v of set hook has a native type but the property SetPropertyHookParameter\Bar::$a does not.',
				41,
			],
			[
				'Parameter $v of set hook does not have a native type but the property SetPropertyHookParameter\Bar::$b does.',
				47,
			],
			[
				'Native type string of set hook parameter $v is not contravariant with native type int of property SetPropertyHookParameter\Bar::$c.',
				53,
			],
			[
				'Native type string of set hook parameter $v is not contravariant with native type int|string of property SetPropertyHookParameter\Bar::$d.',
				59,
			],
			[
				'Type int<1, max> of set hook parameter $v is not contravariant with type int of property SetPropertyHookParameter\Bar::$e.',
				66,
			],
			[
				'Type array<string>|int<1, max> of set hook parameter $v is not contravariant with type int of property SetPropertyHookParameter\Bar::$f.',
				73,
			],
			[
				'Set hook for property SetPropertyHookParameter\MissingTypes::$f has parameter $v with no value type specified in iterable type array.',
				123,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
			[
				'Set hook for property SetPropertyHookParameter\MissingTypes::$g has parameter $value with generic class SetPropertyHookParameter\GenericFoo but does not specify its types: T',
				129,
			],
			[
				'Set hook for property SetPropertyHookParameter\MissingTypes::$h has parameter $value with no signature specified for callable.',
				135,
			],
		]);
	}

}
