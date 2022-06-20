<?php // lint >= 8.1

namespace Bug6064;

function (): void {
	$result = match( rand() <=> rand() ) {
		-1 => 'down',
		0 => 'same',
		1 => 'up'
	};
};
