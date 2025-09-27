<?php declare(strict_types = 1);

namespace Bug12805;

/**
 * @param array<string, array{ rtx?: int }> $operations
 * @return array<string, array{ rtx: int }>
 */
function bug(array $operations): array {
	$base = [];

	foreach ($operations as $operationName => $operation) {
		if (!isset($base[$operationName])) {
			$base[$operationName] = [];
		}
		if (!isset($base[$operationName]['rtx'])) {
			$base[$operationName]['rtx'] = 0;
		}
		$base[$operationName]['rtx'] += $operation['rtx'] ?? 0;
	}

	return $base;
}
