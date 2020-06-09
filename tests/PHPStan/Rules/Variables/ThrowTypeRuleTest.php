<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ThrowTypeRule>
 */
class ThrowTypeRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new ThrowTypeRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false));
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
					'Invalid type ThrowValues\InvalidException to throw.',
					32,
				],
				[
					'Invalid type ThrowValues\InvalidInterfaceException to throw.',
					35,
				],
				[
					'Invalid type Exception|null to throw.',
					38,
				],
				[
					'Throwing object of an unknown class ThrowValues\NonexistentClass.',
					44,
				],
			]
		);
	}

	public function testClassExists(): void
	{
		$this->analyse([__DIR__ . '/data/throw-class-exists.php'], []);
	}

}
