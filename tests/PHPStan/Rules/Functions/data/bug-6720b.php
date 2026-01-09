<?php declare(strict_types = 1);

namespace Bug6720b;

class X {
	/** @return string|void */
	function a() {}

}

function b(?string $a): void {}

function doFoo(?X $x):void {
	b($x?->a());
}
