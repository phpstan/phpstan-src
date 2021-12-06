<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidUnaryOperationRule>
 */
class InvalidUnaryOperationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidUnaryOperationRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-unary.php'], [
			[
				'Unary operation "+" on string results in an error.',
				11,
			],
			[
				'Unary operation "-" on string results in an error.',
				12,
			],
			[
				'Unary operation "+" on \'bla\' results in an error.',
				19,
			],
			[
				'Unary operation "-" on \'bla\' results in an error.',
				20,
			],
			[
				'Unary operation "~" on array{} results in an error.',
				24,
			],
		]);
	}

}
