<?php declare(strict_types=1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<InvalidNullCoalesce>
 */
class InvalidNullCoalesceTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidNullCoalesce();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-null-coalesce.php'], [
			[
				'Null coalesce on type array is always false.',
				10,
			],
			[
				'Null coalesce on type int is always false.',
				11,
			],
			[
				'Null coalesce on type bool is always false.',
				12,
			],
			[
				'Null coalesce on type string is always false.',
				16,
			],
			[
				'Null coalesce on type int|string is always false.',
				27,
			],
			[
			'Null coalesce on type int|false is always false.',
				36,
			],
			[
				'Null coalesce on type null is always true.',
				38,
			],
		]);
	}
}
