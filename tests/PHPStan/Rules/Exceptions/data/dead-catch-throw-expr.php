<?php // lint >= 8.0

namespace DeadCatchThrowExpression;

function (): void {
	try {
		$foo = throw new \InvalidArgumentException('foo');
	} catch (\InvalidArgumentException $e) {

	}
};

function (): void {
	try {
		/** @throws void */
		$foo = throw new \InvalidArgumentException('foo');
	} catch (\InvalidArgumentException $e) {

	}
};
