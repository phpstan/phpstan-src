<?php declare(strict_types = 1);

namespace Bug15245ReturnType;

/**
 * @param non-empty-list<list<int>|null> $groups
 * @return non-empty-list<list<int>|null>
 */
function appendToLastGroup(array $groups, int $value): array
{
	$groups[array_key_last($groups)][] = $value;

	return $groups;
}

/**
 * @param non-empty-list<list<int>> $matrix
 * @return non-empty-list<list<int>>
 */
function setInLastRow(array $matrix, int $column, int $value): array
{
	$matrix[array_key_last($matrix)][$column] = $value;

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
	}

	return $matrix;
}
