<?php declare(strict_types = 1);

namespace Bug2572;

/**
 * @template TE
 * @template TR
 *
 * @param TE $elt
 * @param TR ...$elts
 *
 * @return TE|TR
 */
function collect($elt, ...$elts) {
	$ret = $elt;
	foreach ($elts as $item) {
		if (rand(0, 1)) {
			$ret = $item;
		}
	}
	return $ret;
}

echo collect("a");
echo collect("a", "b", "c");
