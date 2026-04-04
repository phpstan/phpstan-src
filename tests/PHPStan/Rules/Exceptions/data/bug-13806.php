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
	/** @return never */
	public function __toString() {
		throw new \Exception();
	}
}

castToString(new MyString());

class ThrowsException {
	/** @throws \Exception */
	public function __toString(): string {
		throw new \Exception();
	}
}

function castThrowsException(ThrowsException $variable): string {
	try {
		$value = (string) $variable;
	} catch(\Throwable) {
		var_dump("Error thrown during string-conversion!");
		$value = '';
	}

	return $value;
}

class ThrowsVoid {
	/** @throws void */
	public function __toString(): string {
		return 'hello';
	}
}

function castThrowsVoid(ThrowsVoid $variable): string {
	try {
		$value = (string) $variable;
	} catch(\Throwable) {
		var_dump("Error thrown during string-conversion!");
		$value = '';
	}

	return $value;
}

function castIntToString(int $variable): string {
	try {
		$value = (string) $variable;
	} catch(\Throwable) {
		var_dump("Error thrown during string-conversion!");
		$value = '';
	}

	return $value;
}
