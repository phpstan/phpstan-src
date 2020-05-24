<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

/**
 * @extends \PHPStan\Testing\RuleTestCase<DefinedVariableInAnonymousFunctionUseRule>
 */
class DefinedVariableInAnonymousFunctionUseRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $checkMaybeUndefinedVariables;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new DefinedVariableInAnonymousFunctionUseRule($this->checkMaybeUndefinedVariables);
	}

	public function testDefinedVariables(): void
	{
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/defined-variables-anonymous-function-use.php'], [
			[
				'Variable $bar might not be defined.',
				5,
			],
			[
				'Variable $wrongErrorHandler might not be defined.',
				22,
			],
			[
				'Variable $onlyInIf might not be defined.',
				23,
			],
			[
				'Variable $forI might not be defined.',
				24,
			],
			[
				'Variable $forJ might not be defined.',
				25,
			],
			[
				'Variable $anotherVariableFromForCond might not be defined.',
				26,
			],
		]);
	}

}
