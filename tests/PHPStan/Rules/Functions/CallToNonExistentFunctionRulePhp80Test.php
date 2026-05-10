<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToNonExistentFunctionRule>
 */
class CallToNonExistentFunctionRulePhp80Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToNonExistentFunctionRule(self::createReflectionProvider(), true, true);
	}

	public function testBug11810(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11810-php80.php'], [
			[
				'Function array_is_list not found.',
				5,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function enum_exists not found.',
				6,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/bug-11810-php80.neon',
		];
	}

}
