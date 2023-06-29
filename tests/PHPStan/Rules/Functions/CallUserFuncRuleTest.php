<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallUserFuncRule>
 */
class CallUserFuncRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new CallUserFuncRule($reflectionProvider, new FunctionCallParametersCheck(new RuleLevelHelper($reflectionProvider, true, false, true, true, false, true, false), new NullsafeCheck(), new PhpVersion(80000), new UnresolvableTypeHelper(), new PropertyReflectionFinder(), true, true, true, true, true));
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/call-user-func.php'], [
			[
				'Callable passed to call_user_func() invoked with 0 parameters, 1 required.',
				15,
			],
			[
				'Parameter #1 $i of callable passed to call_user_func() expects int, string given.',
				17,
			],
			[
				'Parameter #1 $i of callable passed to call_user_func() expects int, string given.',
				18,
			],
			[
				'Parameter #1 $i of callable passed to call_user_func() expects int, string given.',
				19,
			],
			[
				'Callable passed to call_user_func() invoked with 0 parameters, 2-4 required.',
				30,
			],
			[
				'Callable passed to call_user_func() invoked with 1 parameter, 2-4 required.',
				31,
			],
			[
				'Callable passed to call_user_func() invoked with 0 parameters, at least 2 required.',
				40,
			],
			[
				'Callable passed to call_user_func() invoked with 1 parameter, at least 2 required.',
				41,
			],
			[
				'Result of callable passed to call_user_func() (void) is used.',
				43,
			],
		]);
	}

	public function testBug7057(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7057.php'], []);
	}

}
