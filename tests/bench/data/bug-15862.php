<?php declare(strict_types = 1);

/*
 * instructions: 22.08B
 * cycles: 25.64B
 * memory: 8.009MiB
 */

namespace Bug15862;

/** @param array<mixed> $rows */
function f(array $rows): void {
	$r = [
		'T_7' => 0,
		'T_30' => 0,
		'T_90' => 0,
		'C0_7' => 0,
		'C0_30' => 0,
		'C0_90' => 0,
		'C1_7' => 0,
		'C1_30' => 0,
		'C1_90' => 0,
		'C2_7' => 0,
		'C2_30' => 0,
		'C2_90' => 0,
		'C3_7' => 0,
		'C3_30' => 0,
		'C3_90' => 0,
	];
	foreach ($rows as $row) {
		$a = $row['a'] ?? false;
		$b = $row['b'] ?? false;
		$c = $row['c'] ?? false;
		$s = $row['s'] ?? false;
		if ($a) {
			$r['T_7']++;
			$r['T_30']++;
			$r['T_90']++;
		} elseif ($b) {
			$r['T_30']++;
			$r['T_90']++;
		} elseif ($c) {
			$r['T_90']++;
		}
		switch ($row['t']) {
			case 'c0':
				if ($a) {
					$r['C0_7']++;
					$r['C0_30']++;
					$r['C0_90']++;
				} elseif ($b) {
					$r['C0_30']++;
					$r['C0_90']++;
				} elseif ($c) {
					$r['C0_90']++;
				}
				break;
			case 'c1':
				if ($a) {
					$r['C1_7']++;
					$r['C1_30']++;
					$r['C1_90']++;
				} elseif ($b) {
					$r['C1_30']++;
					$r['C1_90']++;
				} elseif ($c) {
					$r['C1_90']++;
				}
				break;
			case 'c2':
				if ($a) {
					$r['C2_7']++;
					$r['C2_30']++;
					$r['C2_90']++;
				} elseif ($b) {
					$r['C2_30']++;
					$r['C2_90']++;
				} elseif ($c) {
					$r['C2_90']++;
				}
				break;
			case 'c3':
				if ($a) {
					$r['C3_7']++;
					$r['C3_30']++;
					$r['C3_90']++;
				} elseif ($b) {
					$r['C3_30']++;
					$r['C3_90']++;
				} elseif ($c) {
					$r['C3_90']++;
				}
				break;
		}
	}
	\PHPStan\dumpType($r);
}
