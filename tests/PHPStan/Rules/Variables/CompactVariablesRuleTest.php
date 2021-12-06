<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CompactVariablesRule>
 */
class CompactVariablesRuleTest extends RuleTestCase
{

	/** @var bool */
	private $checkMaybeUndefinedVariables;

	protected function getRule(): Rule
	{
		return new CompactVariablesRule($this->checkMaybeUndefinedVariables);
	}

	public function testCompactVariables(): void
	{
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/compact-variables.php'], [
			[
				'Call to function compact() contains undefined variable $bar.',
				22,
			],
			[
				'Call to function compact() contains possibly undefined variable $baz.',
				23,
			],
			[
				'Call to function compact() contains undefined variable $foo.',
				29,
			],
		]);
	}

}
