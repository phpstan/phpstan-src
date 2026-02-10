<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Php\FilterFunctionReturnTypeHelper;
use PHPUnit\Framework\Attributes\RequiresPhp;

/** @extends RuleTestCase<FilterVarRule> */
class FilterVarRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FilterVarRule(
			self::createReflectionProvider(),
			self::getContainer()->getByType(FilterFunctionReturnTypeHelper::class),
		);
	}

	#[RequiresPhp('>= 8.5')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/filter_var_null_and_throw.php'], [
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 5],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 8],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 10],
		]);
	}

}
