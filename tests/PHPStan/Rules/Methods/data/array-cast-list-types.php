<?php declare(strict_types = 1);

namespace ArrayCastListTypes;

class Foo {
	/**
	 * @param list<mixed> $var
	 */
	public function foo($var): void {}

	/**
	 * @param literal-string $literalString
	 * @param non-empty-string $nonEmptyString
	 * @param non-falsy-string $nonFalsyString
	 * @param numeric-string $numericString
	 * @param resource $resource
	 */
	public function bar(string $literalString, string $nonEmptyString, string $nonFalsyString, string $numericString, $resource) {
		$this->foo((array) true);
		$this->foo((array) $literalString);
		$this->foo((array) 1.0);
		$this->foo((array) 1);
		$this->foo((array) $resource);
		$this->foo((array) (fn () => 'closure'));
		$this->foo((array) $nonEmptyString);
		$this->foo((array) $nonFalsyString);
		$this->foo((array) $numericString);
	}
}
