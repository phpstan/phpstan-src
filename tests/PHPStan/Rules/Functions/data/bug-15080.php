<?php declare(strict_types = 1);

namespace Bug15080Return;

/**
 * @param list<int> $list
 * @return list<int>
 */
function appendToList(array $list, int $value): array {
	$list[count($list)] = $value;
	return $list;
}
