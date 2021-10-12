<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

/**
 * @extends \PHPStan\Testing\RuleTestCase<InvalidUnaryOperationRule>
 */
class InvalidUnaryOperationRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
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
