<?php

namespace ClosureSignatureFromUsagesRule;

/** @param callable(int): int $cb */
function takesIntCallback(callable $cb): void
{
}

/** @param callable(int): string $cb */
function takesIntToStringCallback(callable $cb): void
{
}

function register(callable $cb): void
{
}

function (): void {
	$invoked = function ($str) {
		return strlen($str);
	};
	$invoked(1);

	$sent = fn ($str) => strlen($str);
	takesIntCallback($sent);

	$escaped = function ($str) {
		return strlen($str);
	};
	$escaped(1);
	register($escaped);

	$identity = function ($x) {
		return $x;
	};
	takesIntToStringCallback($identity);
};
