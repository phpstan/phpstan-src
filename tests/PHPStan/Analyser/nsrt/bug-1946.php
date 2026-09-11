<?php

namespace Bug1946;

use function PHPStan\Testing\assertType;

$tag = null;
foreach (["a", "b", "c"] as $tag) {
	if ($tag === "a") {
		$tag = null;
		break;
	} elseif ($tag === "b") {
		$tag = null;
		break;
	} else {
		$tag = null;
		break;
	}
}

assertType('null', $tag);

echo strlen($tag);
