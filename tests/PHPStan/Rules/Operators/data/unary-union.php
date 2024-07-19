<?php declare(strict_types = 1);

namespace UnaryBenevolentUnion;

/**
 * @param __benevolent<scalar|null|array|object> $benevolentUnion
 * @param numeric-string|int|float $okUnion
 * @param scalar|null|array|object $union
 * @param __benevolent<array|object> $badBenevolentUnion
 */
function foo($benevolentUnion, $okUnion, $union, $badBenevolentUnion): void
{
	+$benevolentUnion;
	-$benevolentUnion;
	~$benevolentUnion;

	+$okUnion;
	-$okUnion;
	~$okUnion;

	+$union;
	-$union;
	~$union;

	+$badBenevolentUnion;
	-$badBenevolentUnion;
	~$badBenevolentUnion;
}
