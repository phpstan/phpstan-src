<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

/**
 * @extends \PHPStan\Testing\RuleTestCase<VariableCertaintyNullCoalesceRule>
 */
class VariableCertaintyNullCoalesceRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new VariableCertaintyNullCoalesceRule();
	}

	public function testVariableCertaintyInNullCoalesce(): void
	{
		$this->analyse([__DIR__ . '/data/variable-certainty-null.php'], [
			[
				'Variable $scalar on left side of ?? always exists and is not nullable.',
				6,
			],
			[
				'Variable $doesNotExist on left side of ?? is never defined.',
				8,
			],
			[
				'Variable $a on left side of ?? is always null.',
				13,
			],
			[
				'Variable $scalar on left side of ??= always exists and is not nullable.',
				20,
			],
			[
				'Variable $doesNotExist on left side of ??= is never defined.',
				22,
			],
			[
				'Variable $a on left side of ??= is always null.',
				27,
			],
		]);
	}

	public function testNullCoalesceInGlobalScope(): void
	{
		$this->analyse([__DIR__ . '/data/null-coalesce-global-scope.php'], [
			[
				'Variable $bar on left side of ?? always exists and is not nullable.',
				6,
			],
		]);
	}

}
