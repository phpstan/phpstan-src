<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidIncDecOperationRule>
 */
class InvalidIncDecOperationRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		return new InvalidIncDecOperationRule(
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, $this->checkExplicitMixed, $this->checkImplicitMixed, true, false),
			true,
			false,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-inc-dec.php'], [
			[
				'Cannot use ++ on a non-variable.',
				11,
			],
			[
				'Cannot use -- on a non-variable.',
				12,
			],
			[
				'Cannot use ++ on stdClass.',
				17,
			],
			[
				'Cannot use ++ on InvalidIncDec\\ClassWithToString.',
				19,
			],
			[
				'Cannot use -- on InvalidIncDec\\ClassWithToString.',
				21,
			],
			[
				'Cannot use ++ on array{}.',
				23,
			],
			[
				'Cannot use -- on array{}.',
				25,
			],
			[
				'Cannot use ++ on resource.',
				28,
			],
			[
				'Cannot use -- on resource.',
				32,
			],
		]);
	}

	public function testMixed(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/invalid-inc-dec-mixed.php'], [
			[
				'Cannot use ++ on T of mixed.',
				12,
			],
			[
				'Cannot use ++ on T of mixed.',
				14,
			],
			[
				'Cannot use -- on T of mixed.',
				16,
			],
			[
				'Cannot use -- on T of mixed.',
				18,
			],
			[
				'Cannot use ++ on mixed.',
				24,
			],
			[
				'Cannot use ++ on mixed.',
				26,
			],
			[
				'Cannot use -- on mixed.',
				28,
			],
			[
				'Cannot use -- on mixed.',
				30,
			],
			[
				'Cannot use ++ on mixed.',
				36,
			],
			[
				'Cannot use ++ on mixed.',
				38,
			],
			[
				'Cannot use -- on mixed.',
				40,
			],
			[
				'Cannot use -- on mixed.',
				42,
			],
		]);
	}

	public function testUnion(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-inc-dec-union.php'], [
			[
				'Cannot use ++ on array|bool|float|int|object|string|null.',
				24,
			],
			[
				'Cannot use -- on array|bool|float|int|object|string|null.',
				26,
			],
			[
				'Cannot use ++ on (array|object).',
				29,
			],
			[
				'Cannot use -- on (array|object).',
				31,
			],
		]);
	}

}
