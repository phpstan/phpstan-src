<?php declare(strict_types = 1);

namespace Bug8081;

class one {
	/**
	 * @return array<int, string>
	 */
	public function foo() {
		return [];
	}
}

class two extends one {
	public function foo(): array {
		return [];
	}
}

class three extends two {
	public function foo() {
		return [];
	}
}
