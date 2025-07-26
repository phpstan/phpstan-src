<?php declare(strict_types = 1);

namespace ImplodeNamedParameters;

function (): void {
	implode(array: ['']); // error
	join(array: ['']); // error
	implode(separator: '', array: ['']);
	join(separator: '', array: ['']);
};
