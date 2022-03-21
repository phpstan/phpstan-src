<?php // lint >= 8.0

namespace ReturnTypes;

/**
 * @return never
 */
function alwaysThrow() {
	match(true) {
		true => throw new \Exception(),
	};
}

/**
 * @return never
 */
function alwaysThrow2() {
	match(rand(0, 1)) {
		0 => throw new \Exception(),
	};
}

/**
 * @return never
 */
function sometimesThrows() {
	match(rand(0, 1)) {
		0 => throw new \Exception(),
		default => 'test',
	};
}
