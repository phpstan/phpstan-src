<?php

declare(strict_types=1);

namespace Bug9793;

/**
 * @param array<\stdClass> $arr
 * @param \Iterator<\stdClass>|array<\stdClass> $itOrArr
 */
function foo(array $arr, $itOrArr): void
{
	\iterator_to_array($arr);
	\iterator_to_array($itOrArr);
	echo \iterator_count($arr);
	echo \iterator_count($itOrArr);
	\iterator_apply($arr, fn ($x) => $x);
	\iterator_apply($itOrArr, fn ($x) => $x);
}
