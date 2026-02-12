<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12944;

function matcherBugged(string $type): string
{
	match ($type) {
		'type1' => $result = 'foo',
		'type2' => $result = 'bar',
		'type3' => $result = 'baz',
		default => $result = 'qux'
	};

	return $result;
}
