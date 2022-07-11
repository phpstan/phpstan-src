<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_INT_SIZE;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<StrictComparisonOfDifferentTypesRule>
 */
class StrictComparisonOfDifferentTypesRuleTest extends RuleTestCase
{

	private bool $checkAlwaysTrueStrictComparison;

	protected function getRule(): Rule
	{
		return new StrictComparisonOfDifferentTypesRule($this->checkAlwaysTrueStrictComparison);
	}

	public function testStrictComparison(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
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
					'Strict comparison using !== between StrictComparison\Foo|null and 1 will always evaluate to true.',
					154,
				],
				[
					'Strict comparison using === between non-empty-array and null will always evaluate to false.',
					164,
				],
				[
					'Strict comparison using !== between StrictComparison\Node|null and false will always evaluate to true.',
					212,
				],
				[
					'Strict comparison using !== between StrictComparison\Node|null and false will always evaluate to true.',
					255,
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
					'Strict comparison using === between int<0, max> and \'string\' will always evaluate to false.',
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
					'Strict comparison using === between \'foofoofoofoofoofoof…\' and \'foofoofoofoofoofoof…\' will always evaluate to true.',
					654,
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
				],
				[
					'Strict comparison using !== between mixed and 1 will always evaluate to true.',
					812,
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
			],
		);
	}

	public function testStrictComparisonWithoutAlwaysTrue(): void
	{
		$this->checkAlwaysTrueStrictComparison = false;
		$this->analyse(
			[__DIR__ . '/data/strict-comparison.php'],
			[
				[
					'Strict comparison using === between 1 and \'1\' will always evaluate to false.',
					11,
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
					'Strict comparison using === between 1|2|3 and null will always evaluate to false.',
					98,
				],
				[
					'Strict comparison using === between non-empty-array and null will always evaluate to false.',
					140,
				],
				[
					'Strict comparison using === between non-empty-array and null will always evaluate to false.',
					164,
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
					'Strict comparison using === between int<0, max> and \'string\' will always evaluate to false.',
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
				],
				[
					'Strict comparison using === between NAN and NAN will always evaluate to false.',
					980,
				],
				[
					'Strict comparison using !== between INF and INF will always evaluate to false.',
					982,
				],
			],
		);
	}

	public function testStrictComparisonPhp71(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/strict-comparison-71.php'], [
			[
				'Strict comparison using === between null and null will always evaluate to true.',
				20,
			],
		]);
	}

	public function testStrictComparisonPropertyNativeTypesPhp74(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
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
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-2835.php'], []);
	}

	public function testBug1860(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
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
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-3544.php'], []);
	}

	public function testBug2675(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-2675.php'], []);
	}

	public function testBug2220(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-2220.php'], []);
	}

	public function testBug1707(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-1707.php'], []);
	}

	public function testBug3357(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-3357.php'], []);
	}

	public function testBug4848(): void
	{
		if (PHP_INT_SIZE !== 8) {
			$this->markTestSkipped('Test requires 64-bit platform.');
		}
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-4848.php'], [
			[
				'Strict comparison using === between \'18446744073709551615\' and \'9223372036854775807\' will always evaluate to false.',
				7,
			],
		]);
	}

	public function testBug4793(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-4793.php'], []);
	}

	public function testBug5062(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-5062.php'], []);
	}

	public function testBug3366(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-3366.php'], []);
	}

	public function testBug5362(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-5362.php'], [
			[
				'Strict comparison using === between 0 and 1|2 will always evaluate to false.',
				23,
			],
		]);
	}

	public function testBug6939(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;

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
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-7166.php'], []);
	}

	public function testBug7555(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-7555.php'], [
			[
				'Strict comparison using === between 2 and 2 will always evaluate to true.',
				11,
			],
		]);
	}

	public function testBug7257(): void
	{
		$this->checkAlwaysTrueStrictComparison = false;
		$this->analyse([__DIR__ . '/data/bug-7257.php'], []);
	}

	public function testBug5474(): void
	{
		$this->checkAlwaysTrueStrictComparison = false;
		$this->analyse([__DIR__ . '/data/bug-5474.php'], [
			[
				'Strict comparison using !== between array{test: 1} and array{test: 1} will always evaluate to false.',
				25,
			],
		]);
	}

}
