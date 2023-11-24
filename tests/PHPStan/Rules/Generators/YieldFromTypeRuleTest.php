<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generators;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<YieldFromTypeRule>
 */
class YieldFromTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new YieldFromTypeRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false), true);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/yield-from.php'], [
			[
				'Argument of an invalid type int passed to yield from, only iterables are supported.',
				15,
			],
			[
				'Generator expects key type DateTimeImmutable, stdClass given.',
				16,
			],
			[
				'Generator expects value type string, int given.',
				16,
			],
			[
				'Generator expects delegated TSend type int, int|null given.',
				41,
			],
			[
				'Generator expects value type array{DateTime, DateTime, stdClass, DateTimeImmutable}, array{0: DateTime, 1: DateTime, 2: stdClass, 4: DateTimeImmutable} given.',
				74,
				'Array does not have offset 3.',
			],
			[
				'Result of yield from (void) is used.',
				111,
			],
			[
				'Generator expects value type array{opt?: int, req: int}, array{req: 1, foo: 1} given.',
				119,
				"Offset 'foo' is not accepted.",
			],
			[
				'Generator expects value type array{opt?: int, req: int}, array{req: 1, foo: 1}|array{req: 1, opt: 1} given.',
				120,
				"Offset 'foo' is not accepted.",
			],
		]);
	}

}
