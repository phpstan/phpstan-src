<?php declare(strict_types = 1); // lint >= 8.1

namespace PR4663;

use function PHPStan\debugScope;

function (): void {
	$result = match(1){
		default => 'no matches!'
	};
	debugScope();
};
