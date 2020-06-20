<?php

use PHPStan\TrinaryLogic;

\PHPStan\Analyser\assertType('mixed', $foo);
\PHPStan\Analyser\assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

$bar = 'str';
\PHPStan\Analyser\assertVariableCertainty(TrinaryLogic::createYes(), $bar);
\PHPStan\Analyser\assertVariableCertainty(TrinaryLogic::createMaybe(), $baz);

\PHPStan\Analyser\assertType('\'str\'', $bar);

if (!isset($baz)) {
	$baz = 1;
	\PHPStan\Analyser\assertType('1', $baz);
}

\PHPStan\Analyser\assertType('mixed', $baz);

function () {
	\PHPStan\Analyser\assertVariableCertainty(TrinaryLogic::createNo(), $foo);
};
