<?php

namespace ArrowFunctionNever;

function (): void {
	$g = fn (): never => throw new \Exception();
};
