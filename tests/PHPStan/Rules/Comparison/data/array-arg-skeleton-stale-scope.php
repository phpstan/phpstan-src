<?php

namespace ArrayArgSkeletonStaleScopeRule;

/**
 * @template T
 * @param array{first: mixed, value: T, callback: callable(T): void} $spec
 */
function run(array $spec): void
{
}

function doFoo(): void
{
	$i = 0;
	run([
		'first' => $i++,
		'value' => $i,
		'callback' => function ($v): void {
			if ($v === 1) {
				echo 'one';
			}
		},
	]);
}
