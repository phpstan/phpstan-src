<?php
declare(strict_types = 1);

namespace Bug11549;

/**
 * @param  array{0: string, 1?: string} $a
 * @return array{0: string, 1?: string}
 */
function rrr(array $a): array
{
	return array_reverse($a);
}
