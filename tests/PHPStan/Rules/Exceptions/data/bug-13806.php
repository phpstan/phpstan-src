<?php // lint >= 8.0

namespace Bug13806;

function castToString(\Stringable|string $variable): string {
	try {
		$value = (string) $variable;
	} catch(\Throwable) {
		var_dump("Error thrown during string-conversion!");
		$value = '';
	}

	return $value;
}

class MyString {
	public function __toString(): never {
		throw new \Exception();
	}
}

castToString(new MyString());

function castIntToString(int $variable): string {
	try {
		$value = (string) $variable;
	} catch(\Throwable) {
		var_dump("Error thrown during string-conversion!");
		$value = '';
	}

	return $value;
}
