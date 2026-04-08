<?php declare(strict_types = 1);

namespace PHPStan\Command\Bisect;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_map;
use function array_search;
use function count;
use function range;
use function sprintf;

class BinarySearchTest extends TestCase
{

	/**
	 * @param list<string> $items
	 * @param list<string> $expectedIfGood
	 * @param list<string> $expectedIfBad
	 */
	#[DataProvider('dataGetStep')]
	public function testGetStep(
		array $items,
		string $expectedItem,
		array $expectedIfGood,
		array $expectedIfBad,
		int $expectedStepsRemaining,
	): void
	{
		$step = BinarySearch::getStep($items);
		$this->assertSame($expectedItem, $step->item);
		$this->assertSame($expectedIfGood, $step->ifGood);
		$this->assertSame($expectedIfBad, $step->ifBad);
		$this->assertSame($expectedStepsRemaining, $step->stepsRemaining);
	}

	public static function dataGetStep(): iterable
	{
		yield 'two items' => [
			['a', 'b'],
			'a',
			['b'],
			['a'],
			1,
		];

		yield 'three items' => [
			['a', 'b', 'c'],
			'b',
			['c'],
			['a', 'b'],
			2,
		];

		yield 'four items' => [
			['a', 'b', 'c', 'd'],
			'b',
			['c', 'd'],
			['a', 'b'],
			2,
		];

		yield 'five items' => [
			['a', 'b', 'c', 'd', 'e'],
			'c',
			['d', 'e'],
			['a', 'b', 'c'],
			3,
		];

		yield 'eight items' => [
			['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'],
			'd',
			['e', 'f', 'g', 'h'],
			['a', 'b', 'c', 'd'],
			3,
		];

		yield 'sixteen items' => [
			array_map(static fn (int $i): string => (string) $i, range(1, 16)),
			'8',
			['9', '10', '11', '12', '13', '14', '15', '16'],
			['1', '2', '3', '4', '5', '6', '7', '8'],
			4,
		];
	}

	/**
	 * @param list<string> $items
	 */
	#[DataProvider('dataTooFewItems')]
	public function testGetStepWithTooFewItems(array $items): void
	{
		$this->expectException(InvalidArgumentException::class);
		BinarySearch::getStep($items);
	}

	public static function dataTooFewItems(): iterable
	{
		yield 'empty' => [[]];
		yield 'single item' => [['a']];
	}

	/**
	 * @param list<string> $items
	 */
	#[DataProvider('dataFullBisect')]
	public function testFullBisect(array $items, string $firstBadItem): void
	{
		$badIndex = array_search($firstBadItem, $items, true);
		$this->assertNotFalse($badIndex);

		$current = $items;
		$steps = 0;
		$initialStep = BinarySearch::getStep($current);

		while (count($current) > 1) {
			$step = BinarySearch::getStep($current);
			$testIndex = array_search($step->item, $items, true);
			$this->assertNotFalse($testIndex);

			$isBad = $testIndex >= $badIndex;
			$current = $isBad ? $step->ifBad : $step->ifGood;
			$steps++;
		}

		$this->assertCount(1, $current);
		$this->assertSame($firstBadItem, $current[0]);
		$this->assertLessThanOrEqual($initialStep->stepsRemaining, $steps);
	}

	public static function dataFullBisect(): iterable
	{
		$lists = [
			'2 items' => ['a', 'b'],
			'3 items' => ['a', 'b', 'c'],
			'5 items' => ['a', 'b', 'c', 'd', 'e'],
			'8 items' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'],
			'16 items' => array_map(static fn (int $i): string => 'commit-' . $i, range(1, 16)),
		];

		foreach ($lists as $name => $items) {
			foreach ($items as $badItem) {
				yield sprintf('%s, first bad is %s', $name, $badItem) => [$items, $badItem];
			}
		}
	}

}
