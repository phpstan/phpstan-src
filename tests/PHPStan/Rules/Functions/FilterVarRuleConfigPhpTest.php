<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/** @extends RuleTestCase<FilterVarRule> */
class FilterVarRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(FilterVarRule::class);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testRuleInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/filter_var_null_and_throw.php'], [
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 5],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 8],
			['Cannot use both FILTER_NULL_ON_FAILURE and FILTER_THROW_ON_FAILURE.', 10],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range.neon',
		];
	}

}
