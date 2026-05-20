<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_INT_SIZE;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<StrictComparisonOfDifferentTypesRule>
 */
class StrictComparisonOfDifferentTypesRuleTest extends RuleTestCase
{

	private bool $reportAlwaysTrueInLastCondition = false;

	private bool $treatPhpDocTypesAsCertain = true;

	private bool $polluteScopeWithAlwaysIterableForeach = true;

	protected function getRule(): Rule
	{
		return new StrictComparisonOfDifferentTypesRule(
			self::getContainer()->getByType(RicherScopeGetTypeHelper::class),
			new PossiblyImpureTipHelper(true),
			$this->treatPhpDocTypesAsCertain,
			$this->reportAlwaysTrueInLastCondition,
			true,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	protected function shouldPolluteScopeWithAlwaysIterableForeach(): bool
	{
		return $this->polluteScopeWithAlwaysIterableForeach;
	}

	public function testStrictComparison(): void
	{
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse(
			[__DIR__ . '/data/strict-comparison.php'],
			[
				[
					'Strict comparison using === between 1 and 1 will always evaluate to true.',
					10,
				],
				[
					'Strict comparison using === between 1 and \'1\' will always evaluate to false.',
					11,
				],
				[
					'Strict comparison using !== between 1 and \'1\' will always evaluate to true.',
					12,
				],
				[
					'Strict comparison using === between 1 and null will always evaluate to false.',
					14,
				],
				[
					'Strict comparison using === between StrictComparison\Bar and 1 will always evaluate to false.',
					15,
				],
				[
					'Strict comparison using === between 1 and array<StrictComparison\Foo>|bool|StrictComparison\Collection will always evaluate to false.',
					19,
					$tipText,
				],
				[
					'Strict comparison using === between true and false will always evaluate to false.',
					30,
				],
				[
					'Strict comparison using === between false and true will always evaluate to false.',
					31,
				],
				[
					'Strict comparison using === between 1.0 and 1 will always evaluate to false.',
					46,
				],
				[
					'Strict comparison using === between 1 and 1.0 will always evaluate to false.',
					47,
				],
				[
					'Strict comparison using === between string and null will always evaluate to false.',
					69,
				],
				[
					'Strict comparison using !== between string and null will always evaluate to true.',
					76,
				],
				[
					'Strict comparison using !== between StrictComparison\Foo|null and 1 will always evaluate to true.',
					88,
				],
				[
					'Strict comparison using === between 1|2|3 and null will always evaluate to false.',
					98,
				],
				[
					'Strict comparison using !== between StrictComparison\Foo|null and 1 will always evaluate to true.',
					130,
				],
				[
					'Strict comparison using === between non-empty-array and null will always evaluate to false.',
					140,
				],
				[
					'Strict comparison using === between non-empty-array and null will always evaluate to false.',
					150,
				],
				[
					'Strict comparison using !== between StrictComparison\Foo|null and 1 will always evaluate to true.',
					161,
				],
				[
					'Strict comparison using !== between StrictComparison\Node|null and false will always evaluate to true.',
					212,
					$tipText,
				],
				[
					'Strict comparison using !== between StrictComparison\Node|null and false will always evaluate to true.',
					255,
					$tipText,
				],
				[
					'Strict comparison using !== between stdClass and null will always evaluate to true.',
					271,
				],
				[
					'Strict comparison using === between 1 and 2 will always evaluate to false.',
					284,
				],
				[
					'Strict comparison using === between array{X: 1} and array{X: 2} will always evaluate to false.',
					292,
				],
				[
					'Strict comparison using === between array{X: 1, Y: 2} and array{X: 2, Y: 1} will always evaluate to false.',
					300,
				],
				[
					'Strict comparison using === between array{X: 1, Y: 2} and array{Y: 2, X: 1} will always evaluate to false.',
					308,
				],
				[
					'Strict comparison using === between \'/\'|\'\\\\\' and \'//\' will always evaluate to false.',
					320,
				],
				[
					'Strict comparison using === between int<1, max> and \'string\' will always evaluate to false.',
					335,
				],
				[
					'Strict comparison using === between int<1, max> and \'string\' will always evaluate to false.',
					343,
				],
				[
					'Strict comparison using === between int<0, max> and \'string\' will always evaluate to false.',
					360,
				],
				[
					'Strict comparison using === between int<1, max> and \'string\' will always evaluate to false.',
					368,
				],
				[
					'Strict comparison using === between float and \'string\' will always evaluate to false.',
					386,
				],
				[
					'Strict comparison using === between float and \'string\' will always evaluate to false.',
					394,
				],
				[
					'Strict comparison using !== between null and null will always evaluate to false.',
					408,
				],
				[
					'Strict comparison using === between 0 and 0 will always evaluate to true.',
					426,
				],
				[
					'Strict comparison using === between (int<min, 0>|int<2, max>|string) and 1.0 will always evaluate to false.',
					464,
				],
				[
					'Strict comparison using === between (int<min, 0>|int<2, max>|string) and stdClass will always evaluate to false.',
					466,
				],
				[
					'Strict comparison using === between int<0, 1> and 100 will always evaluate to false.',
					622,
				],
				[
					'Strict comparison using === between 100 and \'foo\' will always evaluate to false.',
					624,
				],
				[
					'Strict comparison using === between int<10, max> and \'foo\' will always evaluate to false.',
					635,
				],
				[
					'Strict comparison using === between string|null and 1 will always evaluate to false.',
					685,
				],
				[
					'Strict comparison using === between string|null and 1 will always evaluate to false.',
					695,
				],
				[
					'Strict comparison using === between string|null and 1 will always evaluate to false.',
					705,
				],
				[
					'Strict comparison using === between mixed and \'foo\' will always evaluate to false.',
					808,
					'Type 1|string has already been eliminated from mixed.',
				],
				[
					'Strict comparison using !== between mixed and 1 will always evaluate to true.',
					812,
					'Type 1|string has already been eliminated from mixed.',
				],
				[
					'Strict comparison using === between \'foo\' and \'foo\' will always evaluate to true.',
					846,
				],
				[
					'Strict comparison using === between \'foo\' and \'foo\' will always evaluate to true.',
					849,
				],
				[
					'Strict comparison using === between \'foo\' and \'foo\' will always evaluate to true.',
					857,
				],
				[
					'Strict comparison using === between \'foo\' and \'foo\' will always evaluate to true.',
					876,
				],
				[
					'Strict comparison using === between \'foo\' and \'foo\' will always evaluate to true.',
					879,
				],
				[
					'Strict comparison using === between \'foo\' and \'foo\' will always evaluate to true.',
					887,
				],
				[
					'Strict comparison using === between 1000 and 1000 will always evaluate to true.',
					910,
				],
				[
					'Strict comparison using === between INF and INF will always evaluate to true.',
					979,
				],
				[
					'Strict comparison using === between NAN and NAN will always evaluate to false.',
					980,
				],
				[
					'Strict comparison using !== between INF and INF will always evaluate to false.',
					982,
				],
				[
					'Strict comparison using !== between NAN and NAN will always evaluate to true.',
					983,
				],
				[
					'Strict comparison using === between \'foofoofoofoofoofoof…\' and \'foofoofoofoofoofoof…\' will always evaluate to true.',
					996,
					'Remove remaining cases below this one and this error will disappear too.',
				],
				[
					'Strict comparison using === between lowercase-string|false and \'AB\' will always evaluate to false.',
					1014,
					$tipText,
				],
				[
					'Strict comparison using === between mixed and null will always evaluate to false.',
					1030,
					'Type null has already been eliminated from mixed.',
				],
				[
					'Strict comparison using !== between mixed and null will always evaluate to true.',
					1034,
					'Type null has already been eliminated from mixed.',
				],
				[
					'Strict comparison using !== between array{1, mixed, 3} and array{int, null, int} will always evaluate to true.',
					1048,
					'Offset 1: Type null has already been eliminated from mixed.',
				],
			],
		);
	}

	public function testStrictComparisonPhp71(): void
	{
		$this->analyse([__DIR__ . '/data/strict-comparison-71.php'], [
			[
				'Strict comparison using === between null and null will always evaluate to true.',
				20,
			],
		]);
	}

	public function testStrictComparisonPropertyNativeTypesPhp74(): void
	{
		$this->analyse([__DIR__ . '/data/strict-comparison-property-native-types.php'], [
			[
				'Strict comparison using === between string and null will always evaluate to false.',
				14,
			],
			[
				'Strict comparison using !== between string and null will always evaluate to true.',
				25,
			],
			[
				'Strict comparison using === between null and string will always evaluate to false.',
				36,
			],
			[
				'Strict comparison using !== between null and string will always evaluate to true.',
				47,
			],
		]);
	}

	public function testBug2835(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2835.php'], []);
	}

	public function testBug1860(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1860.php'], [
			[
				'Strict comparison using === between string and null will always evaluate to false.',
				15,
			],
			[
				'Strict comparison using !== between string and null will always evaluate to true.',
				19,
			],
		]);
	}

	public function testBug3544(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3544.php'], []);
	}

	public function testBug2675(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2675.php'], []);
	}

	public function testBug2220(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2220.php'], []);
	}

	public function testBug1707(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1707.php'], []);
	}

	public function testBug3357(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3357.php'], []);
	}

	public function testBug4848(): void
	{
		if (PHP_INT_SIZE !== 8) {
			$this->markTestSkipped('Test requires 64-bit platform.');
		}
		$this->analyse([__DIR__ . '/data/bug-4848.php'], [
			[
				'Strict comparison using === between \'18446744073709551615\' and \'9223372036854775807\' will always evaluate to false.',
				7,
			],
		]);
	}

	public function testBug4793(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4793.php'], []);
	}

	public function testBug5062(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5062.php'], []);
	}

	public function testBug3366(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3366.php'], []);
	}

	public function testBug5362(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5362.php'], [
			[
				'Strict comparison using === between 0 and 1|2 will always evaluate to false.',
				23,
			],
		]);
	}

	public function testBug6939(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->analyse([__DIR__ . '/data/bug-6939.php'], []);
			return;
		}

		$this->analyse([__DIR__ . '/data/bug-6939.php'], [
			[
				'Strict comparison using === between string and false will always evaluate to false.',
				10,
			],
		]);
	}

	public function testBug7166(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7166.php'], []);
	}

	public function testBug7555(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7555.php'], [
			[
				'Strict comparison using === between 2 and 2 will always evaluate to true.',
				11,
			],
		]);
	}

	public function testBug7257(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7257.php'], []);
	}

	public function testBug5474(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5474.php'], [
			[
				'Strict comparison using !== between array{test: 1} and array{test: 1} will always evaluate to false.',
				25,
			],
			[
				'Strict comparison using !== between array{test: 1} and array{test: 5} will always evaluate to true.',
				29,
			],
		]);
	}

	public function testBug7684(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7684.php'], []);
	}

	public function testBug4993(): void
	{
		$errors = [];
		if (PHP_VERSION_ID >= 80000) {
			$errors[] = [
				'Strict comparison using === between non-empty-list<string|null> and null will always evaluate to false.',
				11,
			];
		}

		$this->analyse([__DIR__ . '/data/bug-4993.php'], $errors);
	}

	public function testBug6181(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6181.php'], []);
	}

	public function testBug2851b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2851b.php'], [
			[
				'Strict comparison using === between 0 and 0 will always evaluate to true.',
				21,
			],
		]);
	}

	public function testBug8158(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8158.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug8485(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8485.php'], [
			[
				'Strict comparison using === between Bug8485\E::c and Bug8485\E::c will always evaluate to true.',
				19,
				'Use match expression instead. PHPStan will report unhandled enum cases.',
			],
			[
				'Strict comparison using === between Bug8485\F::c and Bug8485\E::c will always evaluate to false.',
				24,
			],
			[
				'Strict comparison using === between Bug8485\F::c and Bug8485\E::c will always evaluate to false.',
				29,
			],
			[
				'Strict comparison using === between Bug8485\F and Bug8485\E will always evaluate to false.',
				36,
			],
			[
				'Strict comparison using === between Bug8485\F and Bug8485\E::c will always evaluate to false.',
				41,
			],
			[
				'Strict comparison using === between Bug8485\FooEnum::C and Bug8485\FooEnum::C will always evaluate to true.',
				67,
				"• Remove remaining cases below this one and this error will disappear too.\n• Use match expression instead. PHPStan will report unhandled enum cases.",
			],
			[
				'Strict comparison using === between Bug8485\FooEnum::C and Bug8485\FooEnum::C will always evaluate to true.',
				74,
				"• Remove remaining cases below this one and this error will disappear too.\n• Use match expression instead. PHPStan will report unhandled enum cases.",
			],
		]);
	}

	public function testBug8516(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8516.php'], []);
	}

	public function testPhpUnitIntegration(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/phpunit-integration.php'], []);
	}

	public function testBug8586(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8586.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug4242(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4242.php'], []);
	}

	public function testBug3633(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3633.php'], [
			[
				'Strict comparison using === between class-string<$this(Bug3633\HelloWorld)> and \'Bug3633\\\OtherClass\' will always evaluate to false.',
				37,
			],
			[
				'Strict comparison using === between \'Bug3633\\\HelloWorld\' and \'Bug3633\\\HelloWorld\' will always evaluate to true.',
				41,
			],
			[
				'Strict comparison using === between \'Bug3633\\\HelloWorld\' and \'Bug3633\\\OtherClass\' will always evaluate to false.',
				44,
			],
			[
				'Strict comparison using === between class-string<$this(Bug3633\OtherClass)> and \'Bug3633\\\HelloWorld\' will always evaluate to false.',
				64,
			],
			[
				'Strict comparison using === between \'Bug3633\\\OtherClass\' and \'Bug3633\\\HelloWorld\' will always evaluate to false.',
				71,
			],
			[
				'Strict comparison using === between \'Bug3633\\\OtherClass\' and \'Bug3633\\\OtherClass\' will always evaluate to true.',
				74,
			],
			[
				'Strict comparison using === between class-string<$this(Bug3633\FinalClass)> and \'Bug3633\\\HelloWorld\' will always evaluate to false.',
				93,
			],
			[
				'Strict comparison using === between class-string<$this(Bug3633\FinalClass)> and \'Bug3633\\\OtherClass\' will always evaluate to false.',
				96,
			],
			[
				'Strict comparison using === between \'Bug3633\\\FinalClass\' and \'Bug3633\\\FinalClass\' will always evaluate to true.',
				102,
			],
			[
				'Strict comparison using === between \'Bug3633\\\FinalClass\' and \'Bug3633\\\HelloWorld\' will always evaluate to false.',
				106,
			],
			[
				'Strict comparison using === between \'Bug3633\\\FinalClass\' and \'Bug3633\\\OtherClass\' will always evaluate to false.',
				109,
			],
			[
				'Strict comparison using !== between \'Bug3633\\\FinalClass\' and \'Bug3633\\\FinalClass\' will always evaluate to false.',
				112,
			],
			[
				'Strict comparison using === between \'Bug3633\\\FinalClass\' and \'Bug3633\\\FinalClass\' will always evaluate to true.',
				115,
			],
		]);
	}

	public function testLastConditionAlwaysTrue(): void
	{
		$this->analyse([__DIR__ . '/data/strict-comparison-last-condition-always-true.php'], [
			[
				'Strict comparison using === between \'bar\' and \'bar\' will always evaluate to true.',
				15,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testBug3019(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-3019.php'], []);
	}

	public function testBug7578(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-7578.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug6260(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-6260.php'], []);
	}

	public function testBug8736(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8736.php'], []);
	}

	public static function dataLastMatchArm(): iterable
	{
		yield [false, [
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				36,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				"Strict comparison using === between *NEVER* and 'ccc' will always evaluate to false.",
				38,
			],
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				46,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				62,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				79,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]];
		yield [true, [
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				17,
			],
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				30,
			],
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				36,
			],
			[
				"Strict comparison using === between *NEVER* and 'ccc' will always evaluate to false.",
				38,
			],
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				46,
			],
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				62,
			],
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				75,
			],
			[
				"Strict comparison using === between 'bbb' and 'bbb' will always evaluate to true.",
				79,
			],
		]];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	#[RequiresPhp('>= 8.1.0')]
	#[DataProvider('dataLastMatchArm')]
	public function testLastMatchArm(bool $reportAlwaysTrueInLastCondition, array $expectedErrors): void
	{
		$this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
		$this->analyse([__DIR__ . '/data/strict-comparison-last-match-arm.php'], $expectedErrors);
	}

	public function testBug8030(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8030.php'], []);
	}

	public function testBug8776Part1(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8776-1.php'], []);
	}

	public function testBug8776Part2(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8776-2.php'], []);
	}

	public function testBug5978(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$expectedErrors = [
				[
					'Strict comparison using === between non-empty-string and false will always evaluate to false.',
					7,
				],
				[
					'Strict comparison using === between non-empty-string and null will always evaluate to false.',
					7,
				],
			];
		} else {
			$expectedErrors = [];
		}

		$this->analyse([__DIR__ . '/data/bug-5978.php'], $expectedErrors);
	}

	public function testBug9104(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9104.php'], [
			[
				'Strict comparison using === between int<1, max> and 0 will always evaluate to false.',
				12,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testEnumTips(): void
	{
		$this->analyse([__DIR__ . '/data/strict-comparison-enum-tips.php'], [
			[
				'Strict comparison using === between StrictComparisonEnumTips\SomeEnum::Two and StrictComparisonEnumTips\SomeEnum::Two will always evaluate to true.',
				52,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9142(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9142.php'], [
			[
				'Strict comparison using === between $this(Bug9142\MyEnum) and Bug9142\MyEnum::Three will always evaluate to false.',
				18,
			],
			[
				'Strict comparison using === between Bug9142\MyEnum and Bug9142\MyEnum::Three will always evaluate to false.',
				31,
			],
		]);
	}

	public function testBug4918(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4918.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug4061(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4061.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9723(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9723.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9723b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9723b.php'], []);
	}

	public function testBug8366(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-8366.php'], []);
	}

	public function testBug3300(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-3300.php'], []);
	}

	public function testBug11035(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-11035.php'], [
			[
				"Strict comparison using === between '0' and non-falsy-string will always evaluate to false.",
				39,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug9804(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9804.php'], []);
	}

	public function testBug11161(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11161.php'], []);
	}

	public function testBug10697(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10697.php'], []);
	}

	public function testLowercaseString(): void
	{
		$errors = [
			[
				"Strict comparison using === between lowercase-string and 'AB' will always evaluate to false.",
				10,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				"Strict comparison using === between 'AB' and lowercase-string will always evaluate to false.",
				11,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				"Strict comparison using !== between 'AB' and lowercase-string will always evaluate to true.",
				12,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				"Strict comparison using === between lowercase-string and 'aBc' will always evaluate to false.",
				15,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				"Strict comparison using !== between lowercase-string and 'aBc' will always evaluate to true.",
				16,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		];

		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				"Strict comparison using === between lowercase-string|false and 'AB' will always evaluate to false.",
				28,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			];
		} else {
			$errors[] = [
				"Strict comparison using === between lowercase-string and 'AB' will always evaluate to false.",
				28,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			];
		}

		$this->analyse([__DIR__ . '/data/lowercase-string.php'], $errors);
	}

	public function testUppercaseString(): void
	{
		$errors = [
			[
				"Strict comparison using === between uppercase-string and 'ab' will always evaluate to false.",
				10,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				"Strict comparison using === between 'ab' and uppercase-string will always evaluate to false.",
				11,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				"Strict comparison using !== between 'ab' and uppercase-string will always evaluate to true.",
				12,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				"Strict comparison using === between uppercase-string and 'aBc' will always evaluate to false.",
				15,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				"Strict comparison using !== between uppercase-string and 'aBc' will always evaluate to true.",
				16,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		];

		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				"Strict comparison using === between uppercase-string|false and 'ab' will always evaluate to false.",
				28,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			];
		} else {
			$errors[] = [
				"Strict comparison using === between uppercase-string and 'ab' will always evaluate to false.",
				28,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			];
		}

		$this->analyse([__DIR__ . '/data/uppercase-string.php'], $errors);
	}

	public function testBug10493(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10493.php'], []);
	}

	public function testBug7173(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7173.php'], []);
	}

	public function testHashing(): void
	{
		$this->analyse([__DIR__ . '/data/hashing.php'], [
			[
				"Strict comparison using === between lowercase-string&non-falsy-string and 'ABC' will always evaluate to false.",
				9,
			],
			[
				"Strict comparison using === between (lowercase-string&non-falsy-string)|false and 'ABC' will always evaluate to false.",
				12,
			],
			[
				"Strict comparison using === between (lowercase-string&non-falsy-string)|(non-falsy-string&numeric-string) and 'A' will always evaluate to false.",
				31,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug12772(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12772.php'], []);
	}

	public function testBug12748(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12748.php'], []);
	}

	public function testBug3803(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3803.php'], []);
	}

	public function testBug11019(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11019.php'], []);
	}

	public function testBug11485(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11485.php'], []);
	}

	public function testBug10215(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10215.php'], []);
	}

	public function testBug12946(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12946.php'], []);
	}

	public function testBug10884(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10884.php'], []);
	}

	public function testBug3761(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3761.php'], []);
	}

	public function testBug13208(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13208.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug13282(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-13282.php'], []);
	}

	public function testBug10089(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-10089.php'], []);
	}

	public function testBug11609(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11609.php'], [
			[
				'Strict comparison using !== between string and null will always evaluate to true.',
				10,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testPossiblyImpureTip(): void
	{
		$learnMore = ' Learn more: <fg=cyan>https://phpstan.org/blog/remembering-and-forgetting-returned-values</>';
		$impureTipFunction = 'If PossiblyImpureTip\maybeImpureFunction() is impure, add <fg=cyan>@phpstan-impure</> PHPDoc tag above its declaration.' . $learnMore;
		$impureTipMethod = 'If PossiblyImpureTip\MethodCallTest::maybeImpureMethod() is impure, add <fg=cyan>@phpstan-impure</> PHPDoc tag above its declaration.' . $learnMore;
		$impureTipStatic = 'If PossiblyImpureTip\StaticCallTest::maybeImpureStatic() is impure, add <fg=cyan>@phpstan-impure</> PHPDoc tag above its declaration.' . $learnMore;
		$impureTipIntermediate = 'If PossiblyImpureTip\ObjectInvalidationTest::maybeImpureIntermediate() is impure, add <fg=cyan>@phpstan-impure</> PHPDoc tag above its declaration.' . $learnMore;
		$this->analyse([__DIR__ . '/data/possibly-impure-tip.php'], [
			// Function calls: maybe-impure (tip expected)
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				34,
				$impureTipFunction,
			],
			// Function calls: @phpstan-pure (no tip)
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				40,
			],
			// Function calls: @phpstan-impure - no error at all (value not remembered)
			// Function calls: void - cannot appear in === comparison

			// Method calls: maybe-impure (tip expected)
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				85,
				$impureTipMethod,
			],
			// Method calls: @phpstan-pure (no tip)
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				94,
			],
			// Method calls: @phpstan-impure - no error at all (value not remembered)
			// Method calls: void - return type explains the error (no tip)
			[
				'Strict comparison using === between null and null will always evaluate to true.',
				114,
			],

			// Static method calls: maybe-impure (tip expected)
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				156,
				$impureTipStatic,
			],
			// Static method calls: @phpstan-pure (no tip)
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				165,
			],
			// Static method calls: @phpstan-impure - no error at all (value not remembered)
			// Static method calls: void - hasSideEffects()->yes() invalidates

			// Object invalidation: maybe-impure intermediate (tip expected)
			// getValue() is @phpstan-pure, intermediate is maybe-impure
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				233,
				$impureTipIntermediate,
			],
			// Object invalidation: @phpstan-pure intermediate (no tip)
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				244,
			],
			// Object invalidation: @phpstan-impure intermediate - no error ($this invalidated)
			// Object invalidation: void intermediate - no error ($this invalidated)

			// Intermediate maybe-impure call takes priority over direct call
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				294,
				'If PossiblyImpureTip\IntermediateCallPriority::next() is impure, add <fg=cyan>@phpstan-impure</> PHPDoc tag above its declaration.' . $learnMore,
			],
			// No intermediate call: tip points to fetch() itself
			[
				'Strict comparison using === between 1 and 2 will always evaluate to false.',
				303,
				'If PossiblyImpureTip\IntermediateCallPriority::fetch() is impure, add <fg=cyan>@phpstan-impure</> PHPDoc tag above its declaration.' . $learnMore,
			],

			// No tip when return type alone explains the error
			[
				'Strict comparison using === between string and null will always evaluate to false.',
				324,
			],
			[
				'Strict comparison using !== between string and null will always evaluate to true.',
				328,
			],
		]);
	}

	public function testBug11054(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11054.php'], [
			[
				'Strict comparison using === between mixed and array{INF} will always evaluate to false.',
				47,
				'Type array{INF} has already been eliminated from mixed.',
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14407(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-14407.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug13421(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-13421.php'], []);
	}

	public function testBug14446(): void
	{
		$this->polluteScopeWithAlwaysIterableForeach = false;
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-14446.php'], []);
	}

	public function testBug13444(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13444.php'], []);
	}

	public function testBug14473(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14519.php'], []);
	}

}
