<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ShortGetPropertyHookReturnTypeRule>
 */
final class ShortGetPropertyHookReturnTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ShortGetPropertyHookReturnTypeRule(
			new FunctionReturnTypeCheck(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, true, false, false)),
		);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/short-get-property-hook-return.php'], [
			[
				'Get hook for property ShortGetPropertyHookReturn\Foo::$i should return int but returns string.',
				9,
			],
			[
				'Get hook for property ShortGetPropertyHookReturn\Foo::$s should return non-empty-string but returns \'\'.',
				18,
			],
			[
				'Get hook for property ShortGetPropertyHookReturn\GenericFoo::$a should return T of ShortGetPropertyHookReturn\Foo but returns ShortGetPropertyHookReturn\Foo.',
				36,
				'Type ShortGetPropertyHookReturn\Foo is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
			],
			[
				'Get hook for property ShortGetPropertyHookReturn\GenericFoo::$b should return T of ShortGetPropertyHookReturn\Foo but returns ShortGetPropertyHookReturn\Foo.',
				50,
				'Type ShortGetPropertyHookReturn\Foo is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
			],
			[
				'Get hook for property ShortGetPropertyHookReturn\GenericFoo::$c should return T of ShortGetPropertyHookReturn\Foo but returns ShortGetPropertyHookReturn\Foo.',
				59,
				'Type ShortGetPropertyHookReturn\Foo is not always the same as T. It breaks the contract for some argument types, typically subtypes.',
			],
		]);
	}

}
