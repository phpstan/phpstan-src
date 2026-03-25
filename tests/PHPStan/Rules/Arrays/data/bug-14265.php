<?php declare(strict_types = 1);

namespace Bug14265;

/**
 * @param mixed $someVar
 */
function doFoo($someVar): string
{
	$a = [
		'k1' => '1',
	];
	if (!empty($someVar)) {
		$a['k2'] = '1';
	}
	$b = array_reduce(
		array_keys($a),
		fn($carry, $key) => $carry . ' ' . $key . '="' . htmlspecialchars($a[$key]) . '"',
		''
	);

	return $b;
}
