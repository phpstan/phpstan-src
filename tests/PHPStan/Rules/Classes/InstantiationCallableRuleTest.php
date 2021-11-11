<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InstantiationCallableRule>
 */
class InstantiationCallableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InstantiationCallableRule();
	}

	public function testRule(): void
	{
		if (!self::$useStaticReflectionProvider) {
			self::markTestSkipped('Test requires static reflection.');
		}
		$this->analyse([__DIR__ . '/data/instantiation-callable.php'], [
			[
				'Cannot create callable from the new operator.',
				11,
			],
		]);
	}

}
