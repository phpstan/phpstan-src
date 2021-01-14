<?php

/**
 * @var string $foo
 */

use Foo\Bar\Baz;

\PHPStan\Analyser\assertVariableCertainty(\PHPStan\TrinaryLogic::createYes(), $foo);
\PHPStan\Analyser\assertType('string', $foo);
