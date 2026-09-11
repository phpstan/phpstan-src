<?php

namespace Bug1946;

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

echo strlen($tag);
