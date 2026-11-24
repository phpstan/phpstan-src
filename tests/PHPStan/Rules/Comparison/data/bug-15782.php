<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15782;

/** @param list<int> $v */
function rounds(array $v): never {
	$q = floor(6 + 52 / count($v));
	while ($q-- > 0) {
		echo $q;
	}
}
