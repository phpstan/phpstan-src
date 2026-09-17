<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_filter;
use function array_merge;
use function array_values;
use function count;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class ImpossibleCheckTypeFunctionCallRuleWithUncertainPhpDocTypesTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		// @phpstan-ignore argument.type
		return new CompositeRule([
			new ImpossibleCheckTypeFunctionCallRule(
				new ImpossibleCheckTypeHelper(
					self::createReflectionProvider(),
					false,
				),
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				self::getContainer()->getByType(FunctionCallConstantConditionHelper::class),
				false,
				false,
				true,
			),
			new ConstantConditionInTraitRule(),
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/../uncertain-phpdoc-types.neon',
			],
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return false;
	}

	public function testDoNotReportTypesFromPhpDocs(): void
	{
		$this->analyse([__DIR__ . '/data/check-type-function-call-not-phpdoc.php'], [
			[
				'Call to function is_int() with int will always evaluate to true.',
				16,
			],
		]);
	}

	public function testBug4657(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4657.php'], []);
	}

	public function testBug4999(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4999.php'], []);
	}

	public function testBug6938(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6938.php'], []);
	}

	public function testBug5695(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5695.php'], []);
	}

	/** @return list<array{0: string, 1: int, 2?: string}> */
	private static function getLooseComparisonAgainsEnumsIssues(): array
	{
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		return [
			[
				'Call to function in_array() with LooseComparisonAgainstEnums\\FooUnitEnum and array{\'A\'} will always evaluate to false.',
				21,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\\FooUnitEnum, array{\'A\'} and false will always evaluate to false.',
				24,
			],
			[
				'Call to function in_array() with LooseComparisonAgainstEnums\\FooBackedEnum and array{\'A\'} will always evaluate to false.',
				27,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\\FooBackedEnum, array{\'A\'} and false will always evaluate to false.',
				30,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\\FooBackedEnum|LooseComparisonAgainstEnums\\FooUnitEnum, array{\'A\'} and false will always evaluate to false.',
				33,
			],
			[
				'Call to function in_array() with \'A\' and array{LooseComparisonAgainstEnums\\FooUnitEnum} will always evaluate to false.',
				39,
			],
			[
				'Call to function in_array() with arguments \'A\', array{LooseComparisonAgainstEnums\\FooUnitEnum} and false will always evaluate to false.',
				42,
			],
			[
				'Call to function in_array() with \'A\' and array{LooseComparisonAgainstEnums\\FooBackedEnum} will always evaluate to false.',
				45,
			],
			[
				'Call to function in_array() with arguments \'A\', array{LooseComparisonAgainstEnums\\FooBackedEnum} and false will always evaluate to false.',
				48,
			],
			[
				'Call to function in_array() with arguments \'A\', array{LooseComparisonAgainstEnums\\FooBackedEnum|LooseComparisonAgainstEnums\\FooUnitEnum} and false will always evaluate to false.',
				51,
			],
			[
				'Call to function in_array() with LooseComparisonAgainstEnums\FooUnitEnum and array{bool} will always evaluate to false.',
				57,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\FooUnitEnum, array{bool} and false will always evaluate to false.',
				60,
			],
			[
				'Call to function in_array() with LooseComparisonAgainstEnums\FooBackedEnum and array{bool} will always evaluate to false.',
				63,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\FooBackedEnum, array{bool} and false will always evaluate to false.',
				66,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\FooBackedEnum|LooseComparisonAgainstEnums\FooUnitEnum, array{bool} and false will always evaluate to false.',
				69,
			],
			[
				'Call to function in_array() with bool and array{LooseComparisonAgainstEnums\FooUnitEnum} will always evaluate to false.',
				75,
			],
			[
				'Call to function in_array() with arguments bool, array{LooseComparisonAgainstEnums\FooUnitEnum} and false will always evaluate to false.',
				78,
			],
			[
				'Call to function in_array() with bool and array{LooseComparisonAgainstEnums\FooBackedEnum} will always evaluate to false.',
				81,
			],
			[
				'Call to function in_array() with arguments bool, array{LooseComparisonAgainstEnums\FooBackedEnum} and false will always evaluate to false.',
				84,
			],
			[
				'Call to function in_array() with arguments bool, array{LooseComparisonAgainstEnums\FooBackedEnum|LooseComparisonAgainstEnums\FooUnitEnum} and false will always evaluate to false.',
				87,
			],
			[
				'Call to function in_array() with LooseComparisonAgainstEnums\FooUnitEnum and array{null} will always evaluate to false.',
				93,
			],
			[
				'Call to function in_array() with null and array{LooseComparisonAgainstEnums\FooBackedEnum} will always evaluate to false.',
				96,
			],
			[
				'Call to function in_array() with LooseComparisonAgainstEnums\FooUnitEnum and array<string> will always evaluate to false.',
				125,
				$tipText,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\FooUnitEnum, array<string> and false will always evaluate to false.',
				128,
				$tipText,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\FooUnitEnum, array<string> and true will always evaluate to false.',
				131,
				$tipText,
			],
			[
				'Call to function in_array() with string and array<LooseComparisonAgainstEnums\FooUnitEnum> will always evaluate to false.',
				143,
				$tipText,
			],
			[
				'Call to function in_array() with arguments string, array<LooseComparisonAgainstEnums\FooUnitEnum> and false will always evaluate to false.',
				146,
				$tipText,
			],
			[
				'Call to function in_array() with arguments string, array<LooseComparisonAgainstEnums\FooUnitEnum> and true will always evaluate to false.',
				149,
				$tipText,
			],
			[
				'Call to function in_array() with LooseComparisonAgainstEnums\FooUnitEnum::B and non-empty-array<LooseComparisonAgainstEnums\FooUnitEnum::A> will always evaluate to false.',
				159,
				$tipText,
			],
			[
				'Call to function in_array() with LooseComparisonAgainstEnums\FooUnitEnum::A and non-empty-array<LooseComparisonAgainstEnums\FooUnitEnum::A> will always evaluate to true.',
				162,
				$tipText,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\FooUnitEnum::A, non-empty-array<LooseComparisonAgainstEnums\FooUnitEnum::A> and false will always evaluate to true.',
				165,
				'BUG',
				//$tipText,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\FooUnitEnum::A, non-empty-array<LooseComparisonAgainstEnums\FooUnitEnum::A> and true will always evaluate to true.',
				168,
				'BUG',
				//$tipText,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\FooUnitEnum::B, non-empty-array<LooseComparisonAgainstEnums\FooUnitEnum::A> and false will always evaluate to false.',
				171,
				'BUG',
				//$tipText,
			],
			[
				'Call to function in_array() with arguments LooseComparisonAgainstEnums\FooUnitEnum::B, non-empty-array<LooseComparisonAgainstEnums\FooUnitEnum::A> and true will always evaluate to false.',
				174,
				'BUG',
				//$tipText,
			],
		];
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testLooseComparisonAgainstEnumsNoPhpdoc(): void
	{
		$issues = self::getLooseComparisonAgainsEnumsIssues();
		$issues = array_values(array_filter($issues, static fn (array $i) => count($i) === 2));
		$this->analyse([__DIR__ . '/data/loose-comparison-against-enums.php'], $issues);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14429(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14429.php'], []);
	}

	public function testBug11014(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-11014.php'], []);
	}

	public function testBug15250(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15250.php'], []);
	}

}
