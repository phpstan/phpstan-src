<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Php\FilterFunctionFlagsHelper;
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
			self::getContainer()->getByType(FilterFunctionFlagsHelper::class),
			self::getContainer()->getByType(PhpVersion::class),
		);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/filter_var_null_and_throw.php'], [
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 5],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 8],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 10],
		]);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testFilterFunctions(): void
	{
		$this->analyse([__DIR__ . '/data/filter-functions-null-and-throw.php'], [
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 7],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 8],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 9],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 10],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 11],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testRuleWithGlobalRange(): void
	{
		$this->analyse([__DIR__ . '/data/filter_var_null_and_throw_global_range.php'], []);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testRuleGlobalRangePhp85(): void
	{
		$this->analyse([__DIR__ . '/data/filter_var_null_and_global_range_php85.php'], []);
	}

}
