<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug7361;

class Example {
	public function __construct(
		/** @readonly */
		public int $foo
	) {}

	public function doStuff(): void {
		$this->foo = 7;
	}
}
