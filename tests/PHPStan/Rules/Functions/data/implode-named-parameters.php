<?php declare(strict_types = 1); // lint >= 8.0

namespace ImplodeNamedParameters;

function (): void {
	implode(array: ['']); // error
	join(array: ['']); // error
	implode(separator: '', array: ['']);
	join(separator: '', array: ['']);
};
