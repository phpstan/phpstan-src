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
				'Placeholder #1 of function printf expects int, PrintfParamTypes\\FooStringable given',
				15,
			],
			[
				'Placeholder #1 of function printf expects int, int|PrintfParamTypes\\FooStringable given',
				16,
			],
			[
				'Placeholder #1 of function printf expects float, PrintfParamTypes\\FooStringable given',
				17,
			],
			[
				'Placeholder #1 of function sprintf expects int, PrintfParamTypes\\FooStringable given',
				18,
			],
			[
				'Placeholder #1 of function fprintf expects float, PrintfParamTypes\\FooStringable given',
				19,
			],
			[
				'Placeholder #1 of function printf expects int, string given',
				20,
			],
			[
				'Placeholder #1 of function printf expects int, float given',
				21,
			],
			[
				'Placeholder #1 of function printf expects int, SimpleXMLElement given',
				22,
			],
			[
				'Placeholder #1 of function printf expects int, null given',
				23,
			],
			[
				'Placeholder #1 of function printf expects int, true given',
				24,
			],
			[
				'Placeholder #1 of function printf expects int, string given',
				25,
			],
			[
				'Placeholder #1 of function printf expects int, string given',
				26,
			],
		]);
	}

}
