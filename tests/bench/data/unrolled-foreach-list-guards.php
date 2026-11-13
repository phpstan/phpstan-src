<?php declare(strict_types = 1);

/*
 * instructions: 1.690B
 * cycles: 2.013B
 * memory: 39.66MiB
 */

namespace UnrolledForeachListGuards;

function rand_bool(): bool { return (bool) rand(0, 1); }

/** @param array<string, mixed> $changed */
function doUpdate(array $changed): void
{
	$updated = [];
	$props = [
		'billing_p1' => 'billing_p1', 'billing_p2' => 'billing_p2', 'billing_p3' => 'billing_p3',
		'billing_p4' => 'billing_p4', 'billing_p5' => 'billing_p5', 'billing_p6' => 'billing_p6',
		'billing_p7' => 'billing_p7', 'billing_p8' => 'billing_p8', 'billing_p9' => 'billing_p9',
		'billing_p10' => 'billing_p10', 'billing_p11' => 'billing_p11',
	];
	foreach ($props as $metaKey => $prop) {
		$key = substr($prop, 8);
		if (!array_key_exists($key, $changed)) {
			continue;
		}
		if (rand_bool()) {
			$updated[] = $prop;
		}
	}
}

/** @return list<string> */
function checkServer(): array
{
	$missing = [];
	$functions = [
		'strlen' => 'm0', 'strtolower' => 'm1', 'strtoupper' => 'm2', 'trim' => 'm3',
		'ltrim' => 'm4', 'rtrim' => 'm5', 'substr' => 'm6', 'strpos' => 'm7',
		'str_replace' => 'm8', 'explode' => 'm9', 'implode' => 'm10', 'sprintf' => 'm11',
		'json_encode' => 'm12', 'json_decode' => 'm13', 'sha1' => 'm14', 'md5' => 'm15',
	];
	foreach ($functions as $function => $module) {
		if (!function_exists($function)) {
			$missing[] = $module;
		}
	}
	return $missing;
}
