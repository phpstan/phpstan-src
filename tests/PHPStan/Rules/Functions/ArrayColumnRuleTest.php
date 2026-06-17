<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Php\ArrayColumnHelper;
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
			self::getContainer()->getByType(ArrayColumnHelper::class),
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
				72,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'missing' given, but ArrayColumnRuleTest\\FinalObject does not have such property.",
				76,
				$tipText,
			],
			[
				"Parameter #3 \$index_key of function array_column expects a valid property name, 'missing' given, but ArrayColumnRuleTest\\FinalObject does not have such property.",
				78,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'missing' given, but ArrayColumnRuleTest\\FinalObject does not have such property.",
				79,
				$tipText,
			],
			[
				"Parameter #3 \$index_key of function array_column expects a valid property name, 'missing2' given, but ArrayColumnRuleTest\\FinalObject does not have such property.",
				79,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'missing' given, but ArrayColumnRuleTest\\Suit does not have such property.",
				87,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'value' given, but ArrayColumnRuleTest\\PureSuit does not have such property.",
				93,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'missing' given, but ArrayColumnRuleTest\\PureSuit does not have such property.",
				94,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'wrong_key' given, but ArrayColumnRuleTest\\NonFinalObject does not have such property.",
				108,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'missing' given, but ArrayColumnRuleTest\\FinalObject|ArrayColumnRuleTest\\NonFinalObject does not have such property.",
				119,
				$tipText,
			],
			[
				"Parameter #2 \$column_key of function array_column expects a valid property name, 'Price' given, but DateTimeImmutable does not have such property.",
				130,
				$tipText,
			],
		]);
	}

}
