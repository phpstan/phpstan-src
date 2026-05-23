<?php declare(strict_types = 1);

namespace Bug10090;

function doFoo(): void {
	if (rand(0,1)) {
		$shortcut_id = 1;
	}

	$link_mode = isset($shortcut_id) ? "remove" : "add";
	if ($link_mode === "add") {
		echo $shortcut_id;
	}
}

function nullableVariable(): void {
	if (rand(0,1)) {
		$x = rand(0,1) ? 'hello' : null;
	}

	$mode = isset($x) ? "found" : "missing";
	if ($mode === "missing") {
		echo $x;
	}
}

function definitelyDefined(): void {
	$x = rand(0,1) ? 'hello' : null;

	$mode = isset($x) ? "found" : "missing";
	if ($mode === "missing") {
		echo $x;
	}
}
