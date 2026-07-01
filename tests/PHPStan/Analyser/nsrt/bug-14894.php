<?php declare(strict_types = 1);

namespace Bug14894;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, array<mixed>|bool|string> $input
 */
function foobar(array $input): void
{
	foreach ($input as $name => $value) {
		switch ($name) {
			case 'foo':
				break;
			case 'bar' === $name && is_array($value):
				assertType('array<mixed, mixed>', $value);
				break;
			case is_string($value):
				assertType('string', $value);
				break;
		}
	}
}

/**
 * @param int|string $value
 */
function withSwitchTrue($value): void
{
	switch (true) {
		case is_int($value):
			assertType('int', $value);
			break;
		case is_string($value):
			assertType('string', $value);
			break;
	}
}

class Foo
{
}

class Bar
{
}

/**
 * @param Foo|Bar $foo
 */
function withSwitchFalse($foo): void
{
	switch (false) {
		case $foo instanceof Foo:
			assertType('Bug14894\\Bar', $foo);
			break;
	}
}
