<?php

namespace Bug6783;

/**
 * @return array<string, mixed>
 */
function foo(): array
{
	// something from elsewhere (not in the scope of PHPStan)
	return [
		// removing or keeping those lines does/should not change the reporting
		'foo' => [
			'bar' => true,
		]
	];
}

function bar() {
	$data = foo();
	$data = $data['foo'] ?? []; // <<< removing this line suppress the error
	$data += [
		'default' => true,
	];
	foreach (['formatted'] as $field) {
		$data[$field] = empty($data[$field]) ? false : true;
	}

	$bar = $data['bar'];
}

