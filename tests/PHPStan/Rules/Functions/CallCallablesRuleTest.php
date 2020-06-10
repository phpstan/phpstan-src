<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<CallCallablesRule>
 */
class CallCallablesRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$ruleLevelHelper = new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false);
		return new CallCallablesRule(
			new FunctionCallParametersCheck(
				$ruleLevelHelper,
				true,
				true,
				true,
				true
			),
			$ruleLevelHelper,
			true
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/callables.php'], [
			[
				'Trying to invoke string but it might not be a callable.',
				17,
			],
			[
				'Callable \'date\' invoked with 0 parameters, 1-2 required.',
				21,
			],
			[
				'Trying to invoke \'nonexistent\' but it\'s not a callable.',
				25,
			],
			[
				'Parameter #1 $i of callable array($this(CallCallables\Foo), \'doBar\') expects int, string given.',
				33,
			],
			[
				'Trying to invoke array(\'CallCallables\\\\Foo\', \'doStaticBaz\') but it might not be a callable.',
				38,
			],
			[
				'Trying to invoke array(\'CallCallables\\\\Foo\', \'doStaticBaz\') but it might not be a callable.',
				39,
			],
			[
				'Callable array(\'CallCallables\\\\Foo\', \'doStaticBaz\') invoked with 1 parameter, 0 required.',
				41,
			],
			[
				'Callable \'CallCallables\\\\Foo:…\' invoked with 1 parameter, 0 required.',
				43,
			],
			[
				'Call to private method privateFooMethod() of class CallCallables\Foo.',
				54,
			],
			[
				'Closure invoked with 0 parameters, 1-2 required.',
				60,
			],
			[
				'Result of closure (void) is used.',
				61,
			],
			[
				'Closure invoked with 0 parameters, at least 1 required.',
				66,
			],
			[
				'Parameter #1 $i of closure expects int, string given.',
				72,
			],
			[
				'Parameter #1 $str of callable class@anonymous/tests/PHPStan/Rules/Functions/data/callables.php:77 expects string, int given.',
				83,
			],
			[
				'Trying to invoke \'\' but it\'s not a callable.',
				88,
			],
			[
				'Invoking callable on an unknown class CallCallables\Bar.',
				92,
			],
			[
				'Parameter #1 ...$foo of closure expects CallCallables\Foo, array<CallCallables\Foo> given.',
				108,
			],
			[
				'Trying to invoke CallCallables\Baz but it might not be a callable.',
				115,
			],
			[
				'Trying to invoke array(object, \'bar\') but it might not be a callable.',
				133,
			],
			[
				'Closure invoked with 0 parameters, 3 required.',
				148,
			],
			[
				'Closure invoked with 1 parameter, 3 required.',
				149,
			],
			[
				'Closure invoked with 2 parameters, 3 required.',
				150,
			],
			[
				'Trying to invoke array(object, \'yo\') but it might not be a callable.',
				165,
			],
			[
				'Trying to invoke array(object, \'yo\') but it might not be a callable.',
				169,
			],
			[
				'Trying to invoke array(\'CallCallables\\\\CallableInForeach\', \'bar\'|\'foo\') but it might not be a callable.',
				181,
			],
		]);
	}

}
