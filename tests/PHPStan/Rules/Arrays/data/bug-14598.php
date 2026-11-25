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

