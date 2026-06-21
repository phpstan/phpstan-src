<?php declare(strict_types = 1);

namespace Bug4100ReturnType;

function returnsStringWithNestedGenerator(): int
{
	$inner = function (): \Generator {
		yield 1;
	};
	$inner;

	return 'hello';
}
