<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnusedBinaryOperationResultRule>
 */
class UnusedBinaryOperationResultRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedBinaryOperationResultRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-binary-operation-result.php'], [
			[
				'Result of binary operation is not used.',
				10,
			],
			[
				'Result of binary operation is not used.',
				11,
			],
			[
				'Result of binary operation is not used.',
				12,
			],
			[
				'Result of binary operation is not used.',
				13,
			],
			[
				'Result of binary operation is not used.',
				14,
			],
			[
				'Result of binary operation is not used.',
				15,
			],
			[
				'Result of binary operation is not used.',
				17,
			],
			[
				'Result of binary operation is not used.',
				18,
			],
			[
				'Result of binary operation is not used.',
				27,
			],
			[
				'Result of binary operation is not used.',
				28,
			],
			[
				'Result of binary operation is not used.',
				29,
			],
			[
				'Result of binary operation is not used.',
				30,
			],
			[
				'Result of binary operation is not used.',
				31,
			],
			[
				'Result of binary operation is not used.',
				32,
			],
			[
				'Result of binary operation is not used.',
				34,
			],
			[
				'Result of binary operation is not used.',
				35,
			],
			[
				'Result of binary operation is not used.',
				57,
			],
			[
				'Result of binary operation is not used.',
				58,
			],
			[
				'Result of binary operation is not used.',
				59,
			],
			[
				'Result of binary operation is not used.',
				60,
			],
			[
				'Result of binary operation is not used.',
				62,
			],
			[
				'Result of binary operation is not used.',
				63,
			],
		]);
	}

}
