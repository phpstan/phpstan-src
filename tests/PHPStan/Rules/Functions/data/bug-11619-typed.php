<?php declare(strict_types = 1);

namespace Bug11619Typed;

final class Foo implements \Stringable {

	private function __construct(public readonly string $value) {
	}

	public static function fromString(string $string): self {
		return new self($string);
	}

	/**
	 * {@inheritdoc}
	 */
	public function __toString(): string {
		return $this->value;
	}

}

$options = [
	Foo::fromString('c'),
	Foo::fromString('b'),
	Foo::fromString('a'),
	Foo::fromString('ccc'),
	Foo::fromString('bcc'),
];


uasort($options, fn(string $a, string $b) => strnatcasecmp($a, $b));

var_export($options);
