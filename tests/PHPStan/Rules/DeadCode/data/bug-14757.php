<?php declare(strict_types = 1);

namespace Bug14757;

function (): void {
	// StringType is not part of the analysed file set here, but its constructor
	// has an empty body, so instantiating it on a separate line has no effect.
	new \PHPStan\Type\StringType();
};
