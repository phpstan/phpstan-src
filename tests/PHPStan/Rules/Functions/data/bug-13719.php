<?php declare(strict_types = 1);

namespace Bug13719;

function non_variadic(string $name, ?string $greeting = null): void {
	var_dump($name);
}

// Explicitly defined with variadic arguments.
function explicit_variadic(string $name, ?string $greeting = null, string ...$args): void {
	var_dump($name);
}

// Treated as variadic due to usage of `func_get_args()`.
function implicit_variadic(string $name, ?string $greeting = null): void {
	var_dump(func_get_args());
}

// PHPStan correctly detects the error ('greetings' vs 'greeting').
non_variadic('my name', greetings: 'my greeting');

// PHPStan correctly doesn't report anything since the function
// accepts variadic arguments and this is not an error.
explicit_variadic('my name', greetings: 'my greeting');

// ISSUE: PHPStan should detect argument.unknown error here, but it doesn't.
implicit_variadic('my name', greetings: 'my greeting');
