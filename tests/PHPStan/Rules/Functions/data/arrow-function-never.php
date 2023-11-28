<?php // lint >= 7.4

namespace ArrowFunctionNever;

function (): void {
	$g = fn (): never => throw new \Exception();
};
