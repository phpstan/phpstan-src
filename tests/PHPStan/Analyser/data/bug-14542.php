<?php

namespace Bug14542;

class IndexComparator
{
	/**
	 * @param array<mixed> $ids
	 */
	public function compare(mixed $a, mixed $b, array $ids): int
	{
		$indexA = ($index = \array_search($a, $ids)) > -1 ? $index : \PHP_INT_MAX;
		$indexB = ($index = \array_search($b, $ids)) > -1 ? $index : \PHP_INT_MAX;

		return \strnatcmp((string) $indexA, (string) $indexB);
	}
}
