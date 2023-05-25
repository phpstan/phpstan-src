<?php

namespace MagicSignatures;

class Ok {

    function __isset(string $name): bool
	{
		return true;
	}

	function __clone(): void {}

	function __debugInfo(): ?array {
		return [];
	}

	function __set(string $name, mixed $value): void {
	}

	function __set_state(array $properties): object {
		return new self();
	}

	function __sleep(): array {
		return [];
	}

	function __unset(string $name): void {
	}

	function __wakeup(): void {
	}
}

class WrongSignature {
	function __isset(string $name)
	{
		return '';
	}

	function __clone() {
		return '';
	}

	function __debugInfo() {
		return '';
	}

	function __set(string $name, mixed $value) {
		return '';
	}

	function __set_state(array $properties) {
		return '';
	}

	function __sleep() {
		return '';
	}

	function __unset(string $name) {
		return '';
	}

	function __wakeup() {
		return '';
	}

}
