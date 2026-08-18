<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedFunctionUsageRule>
 */
class RestrictedInternalFunctionUsageExtensionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(RestrictedFunctionUsageRule::class);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-internal-tag.php'], [
			[
				'Call to internal function FunctionInternalTagOne\doInternal() from outside its root namespace FunctionInternalTagOne.',
				35,
			],
			[
				'Call to internal function FunctionInternalTagOne\doInternal() from outside its root namespace FunctionInternalTagOne.',
				44,
			],
			[
				'Call to internal function doInternalWithoutNamespace().',
				60,
			],
			[
				'Call to internal function doInternalWithoutNamespace().',
				69,
			],
		]);
	}

}
