<?php // lint >= 8.0

namespace Bug11852;

enum IndexBy {
	case A;
	case B;
}

/**
 * @template T of IndexBy|null
 * @param T $indexBy
 * @return (T is null ? null : string)
 */
function run(?IndexBy $indexBy = null): ?string
{
	return match ($indexBy) {
		IndexBy::A => 'by A',
		IndexBy::B => 'by B',
		null => null,
	};
}
