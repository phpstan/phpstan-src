<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ThrowExprTypeRule>
 */
class ThrowExprTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ThrowExprTypeRule(new RuleLevelHelper(self::createReflectionProvider(), true, false, true, false, false, false, true));
	}

	public function testRule(): void
	{
		$this->analyse(
			[__DIR__ . '/data/throw-values.php'],
			[
				[
					'Invalid type int to throw.',
					29,
				],
				[
					'Invalid type ThrowExprValues\InvalidException to throw.',
					32,
				],
				[
					'Invalid type ThrowExprValues\InvalidInterfaceException to throw.',
					35,
				],
				[
					'Invalid type Exception|null to throw.',
					38,
				],
				[
					'Throwing object of an unknown class ThrowExprValues\NonexistentClass.',
					44,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Invalid type int to throw.',
					65,
				],
			],
		);
	}

	public function testClassExists(): void
	{
		$this->analyse([__DIR__ . '/data/throw-class-exists.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testRuleWithNullsafeVariant(): void
	{
		$this->analyse([__DIR__ . '/data/throw-values-nullsafe.php'], [
			[
				'Invalid type Exception|null to throw.',
				17,
			],
		]);
	}

}
