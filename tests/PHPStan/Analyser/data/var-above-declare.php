<?php

/**
 * @var string $foo
 */

declare(strict_types = 1);

\PHPStan\Analyser\assertVariableCertainty(\PHPStan\TrinaryLogic::createYes(), $foo);
\PHPStan\Analyser\assertType('string', $foo);
