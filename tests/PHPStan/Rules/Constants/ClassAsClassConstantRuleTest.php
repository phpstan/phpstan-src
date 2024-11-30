<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ClassAsClassConstantRule>
 */
class ClassAsClassConstantRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ClassAsClassConstantRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/class-as-class-constant.php'], [
			[
				'A class constant must not be called \'class\'; it is reserved for class name fetching.',
				9,
			],
			[
				'A class constant must not be called \'class\'; it is reserved for class name fetching.',
				16,
			],
		]);
	}

}
