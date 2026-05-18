<?php declare(strict_types=1);

namespace Bug14631;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @template T
	 * @param T&list<int> $items
	 * @return T&list<int>
	 */
	public function sortList(array $items): array
	{
		assertType('list<int>&T (method Bug14631\Foo::sortList(), argument)', $items);
		usort($items, function (int $a, int $b) {
			return $a <=> $b;
		});

		assertType('list<int>&T (method Bug14631\Foo::sortList(), argument)', $items);

		return $items;
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 * @return T&array<string, int>
	 */
	public function sortArray(array $items): array
	{
		assertType('array<string, int>&T (method Bug14631\Foo::sortArray(), argument)', $items);
		usort($items, function (int $a, int $b) {
			return $a <=> $b;
		});

		// T should be dropped because keys changed from string to int
		assertType('list<int>', $items);

		return $items;
	}

	/**
	 * @template T
	 * @param T&list<int> $items
	 * @return T&list<int>
	 */
	public function sortListSort(array $items): array
	{
		sort($items);
		assertType('list<int>&T (method Bug14631\Foo::sortListSort(), argument)', $items);
		return $items;
	}

	/**
	 * @template T
	 * @param T&list<int> $items
	 * @return T&list<int>
	 */
	public function sortListRsort(array $items): array
	{
		rsort($items);
		assertType('list<int>&T (method Bug14631\Foo::sortListRsort(), argument)', $items);
		return $items;
	}

	/**
	 * @template T
	 * @param T&list<int> $items
	 * @return T&list<int>
	 */
	public function sortListShuffle(array $items): array
	{
		shuffle($items);
		assertType('list<int>&T (method Bug14631\Foo::sortListShuffle(), argument)', $items);
		return $items;
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 * @return T&array<string, int>
	 */
	public function sortArraySort(array $items): array
	{
		sort($items);
		// T dropped: keys changed from string to int
		assertType('list<int>', $items);
		return $items;
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 * @return T&array<string, int>
	 */
	public function sortArrayRsort(array $items): array
	{
		rsort($items);
		// T dropped: keys changed from string to int
		assertType('list<int>', $items);
		return $items;
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 * @return T&array<string, int>
	 */
	public function shuffleArray(array $items): array
	{
		shuffle($items);
		// T dropped: keys changed from string to int
		assertType('list<int>', $items);
		return $items;
	}

	/**
	 * @template T
	 * @param T&list<int> $items
	 * @return T&list<int>
	 */
	public function uasortList(array $items): array
	{
		uasort($items, function (int $a, int $b) {
			return $a <=> $b;
		});

		// T preserved, list-ness dropped (key-preserving sort may reorder)
		assertType('array<int<0, max>, int>&T (method Bug14631\Foo::uasortList(), argument)', $items);
		return $items;
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 * @return T&array<string, int>
	 */
	public function uasortArray(array $items): array
	{
		uasort($items, function (int $a, int $b) {
			return $a <=> $b;
		});

		// T preserved: key-preserving sort doesn't change keys
		assertType('array<string, int>&T (method Bug14631\Foo::uasortArray(), argument)', $items);
		return $items;
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 * @return T&array<string, int>
	 */
	public function asortArray(array $items): array
	{
		asort($items);
		// T preserved: key-preserving sort
		assertType('array<string, int>&T (method Bug14631\Foo::asortArray(), argument)', $items);
		return $items;
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 * @return T&array<string, int>
	 */
	public function ksortArray(array $items): array
	{
		ksort($items);
		// T preserved: key-preserving sort
		assertType('array<string, int>&T (method Bug14631\Foo::ksortArray(), argument)', $items);
		return $items;
	}

}
