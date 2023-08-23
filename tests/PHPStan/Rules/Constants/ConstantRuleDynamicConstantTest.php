<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ConstantRule>
 */
class ConstantRuleDynamicConstantTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ConstantRule(['MY_DYNAMIC_CONST']);
	}

	public function testDynamicConstName(): void
	{
		$this->analyse([__DIR__ . '/data/const-dynamic.php'], [
			[
				'Constant ANOTHER_CONSTANT not found.',
				10,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}
