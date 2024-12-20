<?php // lint >= 8.0

declare(strict_types = 1);

namespace UnionTypeError;

final class Foo {
	public string|int $stringOrInt;
	public string|array $stringOrArray;

	public function bar() {
		try {
			$this->stringOrInt = "";
		} catch (\TypeError $e) {}

		try {
			$this->stringOrInt = true;
		} catch (\TypeError $e) {}

		try {
			$this->stringOrArray = [];
		} catch (\TypeError $e) {}

		try {
			$this->stringOrInt = $this->stringOrArray;
		} catch (\TypeError $e) {}
	}
}

