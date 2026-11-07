<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug15128;

use Override;

/**
 * @template T of int|string
 */
class A {
	/**
	 * @param T $val
	 */
	public function __construct(
		protected int|string $val,
	) {
	}

	/**
	 * @return (T is int ? string : int)
	 */
	public function foo(): int|string {
		return is_string($this->val) ? intval($this->val) : (string)$this->val;
	}

	/**
	 * @param (T is int ? string : int) $x
	 */
	public function bar(int|string $x): void {
	}

	/**
	 * @return (T is int ? string : (T is 'a' ? int : string))
	 */
	public function baz(): int|string {
		return 1;
	}
}

/**
 * @extends A<string>
 */
class B extends A {
	#[Override]
	public function foo(): int {
		return intval($this->val);
	}

	#[Override]
	public function bar(int|string $x): void {
	}

	#[Override]
	public function baz(): int|string {
		return 1;
	}
}

/**
 * @template U of int
 * @extends A<U>
 */
class C extends A {
	#[Override]
	public function foo(): string {
		return (string)$this->val;
	}
}
