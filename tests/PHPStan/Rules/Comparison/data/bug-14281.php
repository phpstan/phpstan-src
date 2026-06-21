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
	assert($array[1] === null);
	// everything below is unreachable, the comparisons must not be reported
	assert($array[2] === null);
	assert($array[3] === null);
	assert($array[4] === null);
}

function neverOperand(int $i): void
{
	if ($i !== $i) {
		// $i is never here
		$a = ($i === null);
		$b = ($i !== null);
	}
}
