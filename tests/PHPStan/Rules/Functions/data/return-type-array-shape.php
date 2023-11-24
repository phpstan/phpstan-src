<?php declare(strict_types=1);

namespace ReturnTypeArrayShape;

/** @return array{opt?: int, req: int} */
function test2(): array
{
	if (rand()) {
		return ['req' => 1];
	}

	if (rand()) {
		return ['foo' => 1];
	}

	if (rand()) {
		return rand()
			? ['req' => 1, 'foo' => 1]
			: ['req' => 1, 'foo2' => 1];
	}

	return ['req' => 1, 'foo' => 1];
}
