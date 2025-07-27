<?php declare(strict_types=1);

namespace Bug7076;

/**
 * @param array<string, mixed> $param
 * @return array<string, mixed>
 */
function expectsStringKey(array $param): array
{
	return $param;
}

/**
 * @param array<int|string, mixed> $arguments
 * @return array<string, mixed>
 */
function foo(array $arguments): array
{
	foreach ($arguments as $key => $argument) {
		if (!is_string($key)) {
			throw new \Exception('Key must be a string');
		}
	}

	return $arguments;
}

/**
 * @return array<string, mixed>
 */
function bar(mixed ...$arguments): array
{
	foreach ($arguments as $key => $argument) {
		if (!is_string($key)) {
			throw new \Exception('Key must be a string');
		}

		if (is_int($key)) {
			echo 'int';
		}
	}

	return $arguments;
}

/**
 * @return array<string, mixed>
 */
function baz(mixed ...$arguments): array
{
	foreach ($arguments as $key => $argument) {
		if (is_string($key)) {
			continue;
		}

		throw new \Exception('Key must be a string');
	}

	return $arguments;
}


