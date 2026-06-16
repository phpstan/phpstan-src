<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ArrayColumnRule>
 */
class ArrayColumnRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ArrayColumnRule(
			self::createReflectionProvider(),
			$this->shouldTreatPhpDocTypesAsCertain(),
			true,
		);
	}

	#[RequiresPhp('>= 8.2')]
	public function testRule(): void
	{
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse([__DIR__ . '/data/array-column.php'], [
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'wrong_key' given, but ArrayColumnRuleTest\\NonFinalObject does not have such property.",
				64,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'missing' given, but ArrayColumnRuleTest\\FinalObject does not have such property.",
				68,
				$tipText,
			],
			[
				"Parameter #3 \$index_key of function array_column expects a valid property name, 'missing' given, but ArrayColumnRuleTest\\FinalObject does not have such property.",
				70,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'missing' given, but ArrayColumnRuleTest\\FinalObject does not have such property.",
				71,
				$tipText,
			],
			[
				"Parameter #3 \$index_key of function array_column expects a valid property name, 'missing2' given, but ArrayColumnRuleTest\\FinalObject does not have such property.",
				71,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'wrong_key' given, but ArrayColumnRuleTest\\NonFinalObject does not have such property.",
				96,
			],
		]);
	}

}
