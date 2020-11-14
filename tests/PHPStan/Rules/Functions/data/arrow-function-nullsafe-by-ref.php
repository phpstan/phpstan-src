<?php

namespace ArrowFunctionNullsafeReturnByRef;

function (\stdClass $foo): void {
	fn &() => $foo?->bar;
};

function (\stdClass $foo): void {
	fn &() => $foo->bar;
};

function (\stdClass $foo): void {
	fn () => $foo?->bar;
};
