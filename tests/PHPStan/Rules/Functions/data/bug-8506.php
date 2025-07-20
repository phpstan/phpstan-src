<?php

namespace Bug8506;

function a(): ?int { return rand(0, 1) ? 1 : null; }
function b(int $i, int ...$a): void {}

$arguments = array_filter([
	a(),
	a(),
	a(),
]);

if (!$arguments) {
	return;
}

b(...$arguments);
