<?php declare(strict_types = 1);

namespace Bug15863;

function label(string $s): string { return $s; }

/**
 * @param list<string> $enabled
 * @return array<string, array<int, string>>
 */
function filterGroups(array $enabled): array
{
	$groups = [
		label('a') => ['A1', 'A2'],
		label('b') => ['B1', 'B2', 'B3', 'B4'],
		label('c') => ['C1', 'C2', 'C3'],
	];

	foreach ($groups as &$classes) {
		foreach ($classes as $k => $class) {
			if (!in_array($class, $enabled, true)) {
				unset($classes[$k]);
			}
		}
	}
	unset($classes);

	foreach ($groups as $label => $classes) {
		// $enabled = ['A1'] leaves [0 => 'A1'] in group 'a'
		if (isset($classes[0]) && !isset($classes[1])) {
			echo $label;
		}
	}

	return $groups;
}
