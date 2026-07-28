<?php declare(strict_types = 1);

namespace Bug2032;

function noloop(array $foo) {
	if ($undefined) {}

	foreach ($foo as $bar) {
		if ($undefined) {}
	}
}

function loop(array $foo) {
	foreach ($foo as $bar) {
		if ($undefined) {}
	}
}
