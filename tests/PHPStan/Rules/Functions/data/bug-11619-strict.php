<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug11619Strict;

final class Foo implements \Stringable {

	private function __construct(public readonly string $value) {
	}

	public static function fromString(string $string): self {
		return new self($string);
	}

	public function __toString(): string {
		return $this->value;
	}

}

function test(): void
{
	$options = [
		Foo::fromString('c'),
		Foo::fromString('b'),
		Foo::fromString('a'),
	];

	uasort($options, 'strnatcasecmp');
	usort($options, 'strnatcasecmp');
}
