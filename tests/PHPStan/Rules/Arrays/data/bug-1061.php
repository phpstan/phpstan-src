<?php declare(strict_types = 1);

namespace Bug1061;

class A {
	const KEYS = ["one", "two"];
	const ARR = [
		"two" => 1,
		"three" => 2
	];
}

foreach (A::KEYS as $key) {
	echo A::ARR[$key];
}
