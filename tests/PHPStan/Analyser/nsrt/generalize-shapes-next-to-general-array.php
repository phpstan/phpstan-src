<?php declare(strict_types = 1);

namespace GeneralizeShapesNextToGeneralArray;

use function PHPStan\Testing\assertType;

/**
 * @param list<array{message?: string, path?: string, count?: int}|string> $errors
 */
function doFoo(array $errors): void
{
	$unique = [];
	foreach ($errors as $error) {
		if (!isset($error['path'])) {
			$unique[] = $error;
			continue;
		}

		$key = $error['path'];
		if (!array_key_exists($key, $unique)) {
			$unique[$key] = $error;
			continue;
		}

		$unique[$key] = [
			'message' => $error['message'] ?? null,
			'path' => $error['path'],
			'count' => ($unique[$key]['count'] ?? 1) + ($error['count'] ?? 1),
		];
	}

	assertType('array<0|string, array{message?: string|null, path?: string, count?: int}|string>', $unique);
}
