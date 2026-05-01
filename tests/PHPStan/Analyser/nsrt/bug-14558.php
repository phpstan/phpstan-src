<?php

declare(strict_types = 1);

namespace Bug14558;

use function PHPStan\Testing\assertType;

/** @return non-negative-int */
function num_types(string $g): int {  return 0; }

/** @return non-empty-list<non-empty-string> */
function get_sort_keys(mixed ...$args): array { return ['a']; }

function cond(int $i): bool {  return true; }


// Playground 1: with outer foreach loop
function test1(): void
{
	$cols_cat = [ ];

	foreach ([ 'PrV', 'PrA', 'Acc' ] as $g) {
		$num_types = num_types($g);
		for ($i = 1; $i <= $num_types; $i++) {

			if (cond($i)) {

				$k = 0;
				$tmp_sort_keys = [ ];
				foreach (get_sort_keys($g, $i) as $ce_tri) {
					$k++;
					$tmp_sort_alias = "Tri{$k}_Cat_{$g}{$i}";
					$tmp_sort_keys[$tmp_sort_alias] = $tmp_sort_alias;
				}
				assertType('non-falsy-string', implode(',', $tmp_sort_keys));
				$cols_cat[] = [
					'g' => $g
					, 't' => $i
					, 's' => implode(',', $tmp_sort_keys)
				];

			}
		}

	}

	assertType('list<array{g: \'Acc\'|\'PrA\'|\'PrV\', t: int<1, max>, s: non-falsy-string}>', $cols_cat);
}

// Playground 2: without outer foreach loop
function test2(): void
{
	$cols_cat = [ ];

	$g = 'PrV';
	$num_types = num_types($g);
	for ($i = 1; $i <= $num_types; $i++) {

		if (cond($i)) {

			$k = 0;
			$tmp_sort_keys = [ ];
			foreach (get_sort_keys($g, $i) as $ce_tri) {
				$k++;
				$tmp_sort_alias = "Tri{$k}_Cat_{$g}{$i}";
				$tmp_sort_keys[$tmp_sort_alias] = $tmp_sort_alias;
			}

			assertType('non-falsy-string', implode(',', $tmp_sort_keys));
			$cols_cat[] = [
				'g' => $g
				, 't' => $i
				, 's' => implode(',', $tmp_sort_keys)
			];

		}
	}

	assertType('list<array{g: \'PrV\', t: int<1, max>, s: non-falsy-string}>', $cols_cat);
}
