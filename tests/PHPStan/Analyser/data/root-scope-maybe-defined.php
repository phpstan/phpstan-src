<?php

\PHPStan\Analyser\assertType('mixed', $foo);

$bar = 'str';

\PHPStan\Analyser\assertType('\'str\'', $bar);

if (!isset($baz)) {
	$baz = 1;
	\PHPStan\Analyser\assertType('1', $baz);
}

\PHPStan\Analyser\assertType('mixed', $baz);
