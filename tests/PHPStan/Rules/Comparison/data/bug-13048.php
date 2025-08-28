<?php // lint >= 8.1

namespace Bug13048;

enum IndexBy {
	case A;
	case B;
}

/**
 * @template T of IndexBy|null
 * @param T $indexBy
 */
function run(?IndexBy $indexBy = null): ?string
{
	return match ($indexBy) {
		IndexBy::A => 'by A',
		IndexBy::B => 'by B',
		null => null,
	};
}
