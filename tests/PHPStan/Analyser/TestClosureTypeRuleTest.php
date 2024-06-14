<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TestClosureTypeRule>
 */
class TestClosureTypeRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new TestClosureTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/nsrt/closure-passed-to-type.php'], [
			[
				'Closure type: Closure(mixed): (1|2|3)',
				25,
			],
			[
				'Closure type: Closure(mixed): (1|2|3)',
				35,
			],
		]);
	}

}
