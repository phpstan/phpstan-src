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
				18,
				'Did you mean `$this->foo`? The $ following -> is likely unnecessary.',
			],
			[
				'Unsafe $this->{$array} variable property access with a non-string value.',
				19,
			],
			[
				'Unsafe $this->{$this} variable property access with a non-string value.',
				20,
			],
			[
				'Unsafe $this->bar->{$foo} variable property access with a non-string value.',
				21,
				'Did you mean `$this->bar->foo`? The $ following -> is likely unnecessary.',
			],
			[
				'Unsafe $this->bar->{$this} variable property access with a non-string value.',
				22,
			],
			[
				'Unsafe $this->{$bar}->{$this} variable property access with a non-string value.',
				23,
			],
			[
				'Unsafe $this->{$foo} variable property access with a non-string value.',
				37,
				'Did you mean `$this->foo`? The $ following -> is likely unnecessary.',
			],
			[
				'Unsafe $this->bar->{$foo} variable property access with a non-string value.',
				40,
				'Did you mean `$this->bar->foo`? The $ following -> is likely unnecessary.',
			],
		]);
	}

}
