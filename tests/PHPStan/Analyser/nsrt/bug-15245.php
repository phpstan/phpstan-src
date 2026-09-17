<?php declare(strict_types = 1);

namespace Bug15245;

use function array_key_last;
use function array_search;
use function count;
use function PHPStan\Testing\assertType;

/**
 * @param non-empty-list<list<int>|null> $groups
 * @return non-empty-list<list<int>|null>
 */
function appendToLastGroup(array $groups, int $value): array
{
	$groups[array_key_last($groups)][] = $value;
	assertType('non-empty-list<non-empty-list<int>>', $groups);

	return $groups;
}

/**
 * @param non-empty-list<list<int>> $matrix
 * @return non-empty-list<list<int>>
 */
function setInLastRow(array $matrix, int $column, int $value): array
{
	$matrix[array_key_last($matrix)][$column] = $value;
	assertType('non-empty-list<array<int, int>>', $matrix);

	return $matrix;
}

/**
 * @param list<list<int>> $matrix
 * @return list<list<int>>
 */
function setInExistingRow(array $matrix, int $row, int $column, int $value): array
{
	if (isset($matrix[$row])) {
		$matrix[$row][$column] = $value;
		assertType('non-empty-list<non-empty-array<int, int>>', $matrix);
	}

	return $matrix;
}

/** @param non-empty-list<list<int>> $matrix */
function assignOpInLastRow(array $matrix, int $column): void
{
	$matrix[array_key_last($matrix)][$column] += 1;
	assertType('non-empty-list<array<int, int>>', $matrix);
}

/** @param non-empty-list<list<int>> $matrix */
function postIncInLastRow(array $matrix, int $column): void
{
	$matrix[array_key_last($matrix)][$column]++;
	assertType('non-empty-list<array<int, int>>', $matrix);
}

/** @param non-empty-list<list<int>> $matrix */
function coalesceAssignInLastRow(array $matrix, int $column): void
{
	$matrix[array_key_last($matrix)][$column] ??= 1;
	assertType('non-empty-list<array<int, int>>', $matrix);
}

/** @param non-empty-list<list<int>> $matrix */
function countMinusOneRow(array $matrix, int $column): void
{
	$matrix[count($matrix) - 1][$column] = 1;
	assertType('non-empty-list<array<int, int>>', $matrix);
}

/** @param non-empty-list<non-empty-list<list<int>>> $cube */
function setInLastCell(array $cube, int $column): void
{
	$cube[array_key_last($cube)][0][$column] = 1;
	assertType('non-empty-list<non-empty-list<array<int, int>>>', $cube);
}

/** @param non-empty-list<non-empty-list<list<int>>> $cube */
function appendToLastCell(array $cube): void
{
	$cube[array_key_last($cube)][0][] = 1;
	assertType('non-empty-list<non-empty-list<list<int>>&hasOffsetValue(0, non-empty-list<int>)>', $cube);
}

/** @param list<list<int>> $matrix */
function setInExistingCell(array $matrix, int $row, int $column): void
{
	if (isset($matrix[$row][$column])) {
		$matrix[$row][$column] = 5;
		assertType('non-empty-list<list<int>>', $matrix);
	}
}

class Holder
{

	/** @var non-empty-list<list<int>> */
	public array $matrix = [[]];

	/** @var non-empty-list<int> */
	public array $list = [1];

	/** @var non-empty-list<int> */
	public static array $staticList = [1];

}

function setInLastRowOfProperty(Holder $h, int $column): void
{
	$h->matrix[array_key_last($h->matrix)][$column] = 1;
	assertType('non-empty-list<array<int, int>>', $h->matrix);
}

function overwriteLastKeyOfProperty(Holder $h): void
{
	$h->list[array_key_last($h->list)] = 5;
	assertType('non-empty-list<int>', $h->list);
}

function overwriteCountMinusOneOfProperty(Holder $h): void
{
	$h->list[count($h->list) - 1] = 5;
	assertType('non-empty-list<int>', $h->list);
}

function overwriteSearchedKeyOfProperty(Holder $h, int $needle): void
{
	$h->list[array_search($needle, $h->list, true)] = 5;
	assertType('non-empty-list<int>', $h->list);
}

function overwriteLastKeyOfStaticProperty(): void
{
	Holder::$staticList[array_key_last(Holder::$staticList)] = 5;
	assertType('non-empty-list<int>', Holder::$staticList);
}

/** @param non-empty-list<non-empty-list<int>> $matrix */
function overwriteLastKeyOfRow(array $matrix): void
{
	$matrix[0][array_key_last($matrix[0])] = 5;
	assertType('non-empty-list<non-empty-list<int>>&hasOffsetValue(0, non-empty-list<int>)', $matrix);
}

/** @param non-empty-list<non-empty-list<int>> $matrix */
function overwriteCountMinusOneOfRow(array $matrix): void
{
	$matrix[0][count($matrix[0]) - 1] = 5;
	assertType('non-empty-list<non-empty-list<int>>&hasOffsetValue(0, non-empty-list<int>)', $matrix);
}

/** @param non-empty-list<non-empty-list<int>> $matrix */
function overwriteSearchedKeyOfRow(array $matrix, int $needle): void
{
	$matrix[0][array_search($needle, $matrix[0], true)] = 5;
	assertType('non-empty-list<non-empty-list<int>>&hasOffsetValue(0, non-empty-list<int>)', $matrix);
}

/** @param non-empty-list<non-empty-list<int>> $matrix */
function overwriteLastKeyOfDifferentRow(array $matrix): void
{
	$matrix[0][array_key_last($matrix[1])] = 5;
	assertType('non-empty-list<non-empty-array<int<0, max>, int>>&hasOffsetValue(0, non-empty-array<int<0, max>, int>)', $matrix);
}

/** @param non-empty-list<non-empty-list<int>> $matrix */
function overwriteLastKeyOfNumericStringRow(array $matrix): void
{
	$matrix['0'][array_key_last($matrix[0])] = 5;
	assertType('non-empty-list<non-empty-list<int>>&hasOffsetValue(0, non-empty-list<int>)', $matrix);
}

/** @param non-empty-list<non-empty-list<int>> $matrix */
function overwriteCountMinusOneOfNumericStringRow(array $matrix): void
{
	$matrix[0][count($matrix['0']) - 1] = 5;
	assertType('non-empty-list<non-empty-list<int>>&hasOffsetValue(0, non-empty-list<int>)', $matrix);
}

/** @param non-empty-list<non-empty-list<int>> $matrix */
function overwriteLastKeyOfDifferentNumericStringRow(array $matrix): void
{
	$matrix['0'][array_key_last($matrix['1'])] = 5;
	assertType('non-empty-list<non-empty-array<int<0, max>, int>>&hasOffsetValue(0, non-empty-array<int<0, max>, int>)', $matrix);
}
