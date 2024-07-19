<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidUnaryOperationRule>
 */
class InvalidUnaryOperationRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		return new InvalidUnaryOperationRule(
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, true, false),
			true,
		);
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
			[
				'Unary operation "~" on bool results in an error.',
				36,
			],
			[
				'Unary operation "+" on array results in an error.',
				38,
			],
			[
				'Unary operation "-" on array results in an error.',
				39,
			],
			[
				'Unary operation "~" on array results in an error.',
				40,
			],
			[
				'Unary operation "+" on object results in an error.',
				42,
			],
			[
				'Unary operation "-" on object results in an error.',
				43,
			],
			[
				'Unary operation "~" on object results in an error.',
				44,
			],
			[
				'Unary operation "+" on resource results in an error.',
				50,
			],
			[
				'Unary operation "-" on resource results in an error.',
				51,
			],
			[
				'Unary operation "~" on resource results in an error.',
				52,
			],
			[
				'Unary operation "~" on null results in an error.',
				61,
			],
		]);
	}

	public function testMixed(): void
	{
		$this->checkImplicitMixed = true;
		$this->checkExplicitMixed = true;
		$this->analyse([__DIR__ . '/data/invalid-unary-mixed.php'], [
			[
				'Unary operation "+" on T results in an error.',
				11,
			],
			[
				'Unary operation "-" on T results in an error.',
				12,
			],
			[
				'Unary operation "~" on T results in an error.',
				13,
			],
			[
				'Unary operation "+" on mixed results in an error.',
				18,
			],
			[
				'Unary operation "-" on mixed results in an error.',
				19,
			],
			[
				'Unary operation "~" on mixed results in an error.',
				20,
			],
			[
				'Unary operation "+" on mixed results in an error.',
				25,
			],
			[
				'Unary operation "-" on mixed results in an error.',
				26,
			],
			[
				'Unary operation "~" on mixed results in an error.',
				27,
			],
		]);
	}

	public function testUnion(): void
	{
		$this->analyse([__DIR__ . '/data/unary-union.php'], [
			[
				'Unary operation "+" on array|bool|float|int|object|string|null results in an error.',
				21,
			],
			[
				'Unary operation "-" on array|bool|float|int|object|string|null results in an error.',
				22,
			],
			[
				'Unary operation "~" on array|bool|float|int|object|string|null results in an error.',
				23,
			],
			[
				'Unary operation "+" on (array|object) results in an error.',
				25,
			],
			[
				'Unary operation "-" on (array|object) results in an error.',
				26,
			],
			[
				'Unary operation "~" on (array|object) results in an error.',
				27,
			],
		]);
	}

}
