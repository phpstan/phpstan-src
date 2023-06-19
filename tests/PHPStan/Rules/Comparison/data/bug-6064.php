<?php // lint >= 8.1

namespace Bug6064;

function (): void {
	$result = match( rand() <=> rand() ) {
		-1 => 'down',
		0 => 'same',
		1 => 'up'
	};
};

function (): void {
	$result = match(rand(1, 3)) {
		1 => 'foo',
		2 => 'bar',
		3 => 'baz'
	};
};
