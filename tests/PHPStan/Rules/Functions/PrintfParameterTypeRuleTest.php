<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PrintfParameterTypeRule>
 */
class PrintfParameterTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new PrintfParameterTypeRule(
			new PrintfHelper(new PhpVersion(PHP_VERSION_ID)),
			$reflectionProvider,
			new RuleLevelHelper(
				$reflectionProvider,
				true,
				false,
				true,
				true,
				true,
				true,
				false,
			),
		);
	}

	public function test(): void
	{
		$this->analyse([__DIR__ . '/data/printf-param-types.php'], [
			[
				'Parameter #2 of function printf is expected to be castable to int by placeholder #1 ("%d"), PrintfParamTypes\\FooStringable given.',
				15,
			],
			[
				'Parameter #2 of function printf is expected to be castable to int by placeholder #1 ("%d"), int|PrintfParamTypes\\FooStringable given.',
				16,
			],
			[
				'Parameter #2 of function printf is expected to be castable to float by placeholder #1 ("%f"), PrintfParamTypes\\FooStringable given.',
				17,
			],
			[
				'Parameter #2 of function sprintf is expected to be castable to int by placeholder #1 ("%d"), PrintfParamTypes\\FooStringable given.',
				18,
			],
			[
				'Parameter #3 of function fprintf is expected to be castable to float by placeholder #1 ("%f"), PrintfParamTypes\\FooStringable given.',
				19,
			],
			[
				'Parameter #2 of function printf is expected to be int by placeholder #1 ("%*s" (width)), string given.',
				20,
			],
			[
				'Parameter #2 of function printf is expected to be int by placeholder #1 ("%*s" (width)), float given.',
				21,
			],
			[
				'Parameter #2 of function printf is expected to be int by placeholder #1 ("%*s" (width)), SimpleXMLElement given.',
				22,
			],
			[
				'Parameter #2 of function printf is expected to be int by placeholder #1 ("%*s" (width)), null given.',
				23,
			],
			[
				'Parameter #2 of function printf is expected to be int by placeholder #1 ("%*s" (width)), true given.',
				24,
			],
			[
				'Parameter #2 of function printf is expected to be int by placeholder #1 ("%.*s" (precision)), string given.',
				25,
			],
			[
				'Parameter #2 of function printf is expected to be int by placeholder #2 ("%3$.*s" (precision)), string given.',
				26,
			],
			[
				'Parameter #2 of function printf is expected to be castable to float by placeholder #1 ("%1$-\'X10.2f"), PrintfParamTypes\\FooStringable given.',
				27,
			],
			[
				'Parameter #2 of function printf is expected to be castable to float by placeholder #2 ("%1$*.*f" (value)), PrintfParamTypes\\FooStringable given.',
				28,
			],
			[
				'Parameter #4 of function printf is expected to be castable to float by placeholder #1 ("%3$f"), PrintfParamTypes\\FooStringable given.',
				29,
			],
			[
				'Parameter #2 of function printf is expected to be castable to float by placeholder #1 ("%1$f"), PrintfParamTypes\\FooStringable given.',
				30,
			],
			[
				'Parameter #2 of function printf is expected to be castable to int by placeholder #2 ("%1$d"), PrintfParamTypes\\FooStringable given.',
				30,
			],
			[
				'Parameter #2 of function printf is expected to be int by placeholder #1 ("%1$*d" (width)), float given.',
				31,
			],
		]);
	}

}
