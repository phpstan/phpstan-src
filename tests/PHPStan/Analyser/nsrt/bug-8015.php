<?php declare(strict_types = 1);

namespace Bug8015;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, string|string[]> $items
 * @return array<string, mixed>
 */
function extractParameters(array $items): array
{
	$config = [];
	foreach ($items as $itemName => $item) {
		if (is_array($item)) {
			$config['things'] = [];
			assertType('array{}', $config['things']);
			foreach ($item as $thing) {
				assertType('list<string>', $config['things']);
				$config['things'][] = (string) $thing;
			}
			assertType('list<string>', $config['things']);
		} else {
			$config[$itemName] = (string) $item;
		}
	}
	assertType('list<string>|string', $config['things']);

	return $config;
}
