<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidAssignVarRule>
 */
class InvalidAssignVarRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidAssignVarRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/invalid-assign-var.php'], [
			[
				'Nullsafe operator cannot be on left side of assignment.',
				12,
			],
			[
				'Nullsafe operator cannot be on left side of assignment.',
				13,
			],
			[
				'Nullsafe operator cannot be on left side of assignment.',
				14,
			],
			[
				'Nullsafe operator cannot be on left side of assignment.',
				16,
			],
			[
				'Nullsafe operator cannot be on left side of assignment.',
				17,
			],
		]);
	}

}
