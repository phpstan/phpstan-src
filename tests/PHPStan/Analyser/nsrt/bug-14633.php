<?php declare(strict_types=1);

namespace Bug14633;

use function PHPStan\Testing\assertType;

/**
 * Tests for IntersectionType preserving TemplateType in array methods.
 * Pattern: @template T with T&list<V> or T&array<K,V>
 */
class IntersectionTemplatePreservation
{

	/**
	 * @template T
	 * @param T&list<int> $items
	 */
	public function popList(array $items): void
	{
		array_pop($items);
		assertType('list<int>&T (method Bug14633\IntersectionTemplatePreservation::popList(), argument)', $items);
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 */
	public function popArray(array $items): void
	{
		array_pop($items);
		assertType('array<string, int>&T (method Bug14633\IntersectionTemplatePreservation::popArray(), argument)', $items);
	}

	/**
	 * @template T
	 * @param T&list<int> $items
	 */
	public function shiftList(array $items): void
	{
		array_shift($items);
		assertType('list<int>&T (method Bug14633\IntersectionTemplatePreservation::shiftList(), argument)', $items);
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 */
	public function shiftArray(array $items): void
	{
		array_shift($items);
		assertType('array<string, int>&T (method Bug14633\IntersectionTemplatePreservation::shiftArray(), argument)', $items);
	}

	/**
	 * @template T
	 * @param T&list<int> $items
	 */
	public function reverseList(array $items): void
	{
		$reversed = array_reverse($items);
		assertType('list<int>&T (method Bug14633\IntersectionTemplatePreservation::reverseList(), argument)', $reversed);
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 */
	public function reverseArrayPreserveKeys(array $items): void
	{
		$reversed = array_reverse($items, true);
		assertType('array<string, int>&T (method Bug14633\IntersectionTemplatePreservation::reverseArrayPreserveKeys(), argument)', $reversed);
	}

	/**
	 * @template T
	 * @param T&list<int> $items
	 */
	public function sliceList(array $items): void
	{
		$sliced = array_slice($items, 1);
		assertType('list<int>&T (method Bug14633\IntersectionTemplatePreservation::sliceList(), argument)', $sliced);
	}

	/**
	 * @template T
	 * @param T&array<string, int> $items
	 */
	public function sliceArrayPreserveKeys(array $items): void
	{
		$sliced = array_slice($items, 0, 5, true);
		assertType('array<string, int>&T (method Bug14633\IntersectionTemplatePreservation::sliceArrayPreserveKeys(), argument)', $sliced);
	}

	/**
	 * @template T
	 * @param T&list<int> $items
	 */
	public function arrayValuesOnList(array $items): void
	{
		$values = array_values($items);
		assertType('list<int>&T (method Bug14633\IntersectionTemplatePreservation::arrayValuesOnList(), argument)', $values);
	}

	/**
	 * @template T
	 * @param T&list<int> $items
	 */
	public function arrayFilterOnList(array $items): void
	{
		$filtered = array_filter($items);
		assertType('array<int<0, max>, int<min, -1>|int<1, max>>&T (method Bug14633\IntersectionTemplatePreservation::arrayFilterOnList(), argument)', $filtered);
	}

	/**
	 * @template T
	 * @param T&array<string, int|false> $items
	 */
	public function arrayFilterOnArray(array $items): void
	{
		$filtered = array_filter($items);
		assertType('array<string, int<min, -1>|int<1, max>>&T (method Bug14633\IntersectionTemplatePreservation::arrayFilterOnArray(), argument)', $filtered);
	}

}

/**
 * Tests for ArrayType methods preserving template via $this->withTypes().
 * Pattern: @template T of array<K,V>
 */
class ArrayTypeTemplatePreservation
{

	/**
	 * @template T of array<string, int>
	 * @param T $items
	 */
	public function filterArrayRemovingFalsey(array $items): void
	{
		$result = array_filter($items);
		assertType('T of array<string, int<min, -1>|int<1, max>> (method Bug14633\ArrayTypeTemplatePreservation::filterArrayRemovingFalsey(), argument)', $result);
	}

	/**
	 * @template T of array<string, int>
	 * @param T $items
	 * @param array<string, mixed> $other
	 */
	public function intersectKeyArray(array $items, array $other): void
	{
		$result = array_intersect_key($items, $other);
		assertType('T of array<string, int> (method Bug14633\ArrayTypeTemplatePreservation::intersectKeyArray(), argument)', $result);
	}

	/**
	 * @template T of array<int, int>
	 * @param T $items
	 */
	public function sliceArray(array $items): void
	{
		$result = array_slice($items, 1);
		assertType('T of array<int<0, max>, int> (method Bug14633\ArrayTypeTemplatePreservation::sliceArray(), argument)&list', $result);
	}

}
