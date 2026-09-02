<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DerivedScopeGetTypeRule>
 */
class DerivedScopeGetTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DerivedScopeGetTypeRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/derived-scope-get-type.php'], [
			[
				"'weight' / 'weight'",
				11,
			],
		]);
	}

}
