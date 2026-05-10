<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToNonExistentFunctionRule>
 */
class CallToNonExistentFunctionRulePhp74Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CallToNonExistentFunctionRule(self::createReflectionProvider(), true, true);
	}

	public function testBug11810(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11810.php'], [
			[
				'Function str_ends_with not found.',
				5,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function str_starts_with not found.',
				6,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function str_contains not found.',
				7,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/bug-11810.neon',
		];
	}

}
