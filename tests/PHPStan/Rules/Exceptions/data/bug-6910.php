<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug6910;

class RedFish {}

class BlueFish {}

class Net {
	public RedFish|BlueFish $heldFish;
	public int $prop;

	/**
	 * @throws void
	 */
	public function dropFish(): void {
		match ($this->heldFish instanceof RedFish) {
			true => 'hello',
			false => 'world',
		};
	}

	/**
	 * @throws void
	 * @param 'hello'|'world' $string
	 */
	public function issetFish(string $string): void {
		match ($string === 'hello') {
			true => 'hello',
			false => 'world',
		};
	}

	/**
	 * @throws void
	 */
	public function anotherFish(bool $bool): void {
		match ($bool) {
			true => 'hello',
			false => 'world',
		};
	}
}
