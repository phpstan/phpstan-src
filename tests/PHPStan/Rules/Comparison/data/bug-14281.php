<?php declare(strict_types = 1);

namespace Bug14281Rule;

function test(): void
{
	$array = [
		null,
		0,
		'some-string',
		new \stdClass(),
		['some' => 'value'],
	];

	assert($array[0] === null);
	// $array[1] is 0, so this assertion collapses the array to never
	assert($array[1] === null);
	// offset access on the never array stays never (instead of *ERROR*),
	// so the comparisons below are reported as impossible
	assert($array[2] === null);
	assert($array[3] === null);
	assert($array[4] === null);
}

function neverOperand(int $i): void
{
	if ($i !== $i) {
		// $i is never here, so the comparisons are reported as impossible
		$a = ($i === null);
		$b = ($i !== null);
	}
}
