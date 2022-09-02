<?php // lint >= 8.0

namespace Bug7904;

interface Test {
	public static function create(): static;
}

$impl = new class implements Test {
	public static function create(): static {
		return new self();
	}
};
