<?php

namespace Bug12397;

/**
 * @return list<string>
 */
function matchStuff(string $string) : array {
	$m = preg_match('#\b([A-Z]{2,})-(\d+)#', $string, $match);
	return $match;
}
