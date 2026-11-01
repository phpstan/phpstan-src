<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoOptionalParameterRule>
 */
final class NoOptionalParameterRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NoOptionalParameterRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/no-optional-parameter.php'], [
			[
				'Parameter $optional of function NoOptionalParameter\\internalFunction() must not be optional. Optional parameters are allowed only in @api code.',
				5,
			],
			[
				'Parameter $optional of method NoOptionalParameter\\InternalClass::__construct() must not be optional. Optional parameters are allowed only in @api code.',
				17,
			],
			[
				'Parameter $optional of method NoOptionalParameter\\InternalClass::method() must not be optional. Optional parameters are allowed only in @api code.',
				21,
			],
			[
				'Parameter $optional of method NoOptionalParameter\\ApiClass::__construct() must not be optional. Optional parameters are allowed only in @api code.',
				39,
			],
			[
				'Parameter $optional of method NoOptionalParameter\\ApiClass::privateMethod() must not be optional. Optional parameters are allowed only in @api code.',
				51,
			],
			[
				'Parameter $optional of method NoOptionalParameter\\FinalApiClass::protectedMethod() must not be optional. Optional parameters are allowed only in @api code.',
				66,
			],
			[
				'Parameter $optional of method NoOptionalParameter\\DoNotImplementOnlyClass::method() must not be optional. Optional parameters are allowed only in @api code.',
				76,
			],
			[
				'Parameter $b of method NoOptionalParameter\\InternalInterface::method() must not be optional. Optional parameters are allowed only in @api code.',
				85,
			],
			[
				'Parameter $c of method NoOptionalParameter\\Implementation::method() must not be optional. Optional parameters are allowed only in @api code.',
				92,
			],
			[
				'Parameter $another of method NoOptionalParameter\\ChildOfInternalClass::method() must not be optional. Optional parameters are allowed only in @api code.',
				101,
			],
			[
				'Parameter $c of method NoOptionalParameter\\TraitImplementingInterface::method() must not be optional. Optional parameters are allowed only in @api code.',
				120,
			],
		]);
	}

}
