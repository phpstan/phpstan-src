<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generators;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<YieldTypeRule>
 */
class YieldTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new YieldTypeRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/yield.php'], [
			[
				'Generator expects value type int, string given.',
				14,
			],
			[
				'Generator expects key type string, int given.',
				15,
			],
			[
				'Generator expects value type int, null given.',
				15,
			],
			[
				'Generator expects key type string, int given.',
				16,
			],
			[
				'Generator expects key type string, int given.',
				17,
			],
			[
				'Generator expects value type int, string given.',
				17,
			],
			[
				'Generator expects value type array{0: DateTime, 1: DateTime, 2: stdClass, 4: DateTimeImmutable}, array{DateTime, DateTime, stdClass, DateTimeImmutable} given.',
				25,
				'Array does not have offset 4.',
			],
			[
				'Result of yield (void) is used.',
				137,
			],
			[
				'Result of yield (void) is used.',
				138,
			],
		]);
	}

	public function testBug7484(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7484.php'], [
			[
				'Generator expects key type K of int|string, (K of int)|string given.',
				21,
				'Type string is not always the same as K. It breaks the contract for some argument types, typically subtypes.',
			],
		]);
	}

}
