<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<EmptyArrayItemRule>
 */
class EmptyArrayItemRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EmptyArrayItemRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/empty-array-item.php'], [
			[
				'Cannot use empty array elements in arrays on line 5',
				5,
			],
		]);
	}

}
