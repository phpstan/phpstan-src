<?php declare(strict_types = 1);

namespace Bug7320;

/**
 * @param callable(int=): void $c
 */
function foo(callable $c): void {
	$c();
}

function () {
	foo(function (int $a): void {});
};
