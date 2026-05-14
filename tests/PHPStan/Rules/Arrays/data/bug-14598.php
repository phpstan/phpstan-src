<?php declare(strict_types = 1);

namespace Bug14598;

/**
 * @param array<'A'|'B'|'C', array<int<0, 3>, 'off'|'on'>> $raw
 * @return array<'A'|'B'|'C', string>
 */
function buildData(array $raw): array
{
	$return = [];
	foreach ($raw as $id => $sensors) {
		$tmp[$id] = [];
		$last = "off";
		foreach ($sensors as $i => $stat) {
			if ($last !== $stat) {
				$tmp[$id][] = sprintf("%02d", $i);
				$last = $stat;
			}
		}
		$return[$id] = count($tmp[$id])
			? implode(",", $tmp[$id])
			: "invalid";
	}

	return $return;
}

/**
 * @param array<'A'|'B'|'C', array<int, int>> $raw
 */
function simpleNestedForeach(array $raw): void
{
	$tmp = [];
	foreach ($raw as $id => $sensors) {
		$tmp[$id] = [];
		foreach ($sensors as $i => $stat) {
			if ($i > 0) {
				$tmp[$id][] = $stat;
			}
		}
		echo count($tmp[$id]);
	}
}

/**
 * @param list<'A'|'B'|'C'> $keys
 * @param array<int, int> $values
 */
function nestedWhileLoop(array $keys, array $values): void
{
	$tmp = [];
	foreach ($keys as $id) {
		$tmp[$id] = [];
		$i = 0;
		while ($i < count($values)) {
			if ($values[$i] > 0) {
				$tmp[$id][] = $values[$i];
			}
			$i++;
		}
		echo count($tmp[$id]);
	}
}
