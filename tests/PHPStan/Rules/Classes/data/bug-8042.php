<?php // lint >= 8.0

namespace Bug8042;

class A {}
class B {}

function test(A|B $ab): string {
	return match (true) {
		$ab instanceof A => 'a',
		$ab instanceof B => 'b',
	};
}

function test2(A|B $ab): string {
	return match (true) {
		$ab instanceof A => 'a',
		$ab instanceof B => 'b',
		rand(0, 1) => 'never'
	};
}

function test3(A|B $ab): string {
	return match (true) {
		$ab instanceof A => 'a',
		$ab instanceof B => 'b',
		default => 'never'
	};
}
