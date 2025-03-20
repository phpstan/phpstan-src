<?php declare(strict_types = 1);

namespace Bug7880;

class C {
	/** @var array{a: string, b: string}|null */
	private $a = null;

	public function foo(): void {
		if ($this->a !== null) {
			$this->a['b'] = "baz";
		}
	}
}
