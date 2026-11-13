<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\FunctionByRef;

/**
 * @return list<int>
 */
function &values(): array
{
	static $values = [1];

	return $values;
}
