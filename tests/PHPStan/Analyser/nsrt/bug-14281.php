<?php declare(strict_types = 1);

namespace Bug14281;

use function PHPStan\Testing\assertType;

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
	assertType("array{null, 0, 'some-string', stdClass, array{some: 'value'}}", $array);

	// $array[1] is 0, so this assertion can never hold and collapses the array
	assert($array[1] === null);
	assertType('*NEVER*', $array);

	// offset access on a never array must stay never instead of becoming *ERROR*
	assertType('*NEVER*', $array[2]);
	assert($array[2] === null);
	assertType('*NEVER*', $array);
}

function neverVariable(int $i): void
{
	if ($i !== $i) {
		assertType('*NEVER*', $i);
		assertType('bool', $i === null);
		assertType('bool', $i !== null);
	}
}
