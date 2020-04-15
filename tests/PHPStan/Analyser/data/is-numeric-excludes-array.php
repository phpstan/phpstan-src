<?php
/** @see https://github.com/phpstan/phpstan/issues/3133 */

function () {
	/** @var array<string>|string|int $s */
	$s = doFoo();
	if (!is_numeric($s)) {
		\PHPStan\Analyser\assertType('array<string>|string', $s);
	}

	\PHPStan\Analyser\assertType('string|int', $s);
};
