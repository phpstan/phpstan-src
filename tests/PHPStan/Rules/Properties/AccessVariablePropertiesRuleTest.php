<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<AccessVariablePropertiesRule>
 */
class AccessVariablePropertiesRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	private bool $checkUnionTypes;

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new AccessVariablePropertiesRule(
			new RuleLevelHelper($reflectionProvider, true, $this->checkThisOnly, $this->checkUnionTypes, false, false, true, false),
			new ExprPrinter(new Printer()),
		);
	}

	public function testAccessVariableProperties(): void
	{
		$this->checkThisOnly = false;
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/../Comparison/data/bug-9475.php'], [
			[
				'Unsafe $this->{$foo} variable property access with a non-string value.',
				16,
				'Did you mean `$this->foo`? The $ following -> is likely unnecessary.',
			],
			[
				'Unsafe $this->{$array} variable property access with a non-string value.',
				17,
			],
			[
				'Unsafe $this->{$this} variable property access with a non-string value.',
				18,
			],
			[
				'Unsafe $this->{$foo} variable property access with a non-string value.',
				30,
				'Did you mean `$this->foo`? The $ following -> is likely unnecessary.',
			],
		]);
	}

}
