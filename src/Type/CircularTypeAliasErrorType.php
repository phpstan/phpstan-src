<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ShadowedByTurboExtension;

/** @api */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/CircularTypeAliasErrorType.cpp')]
class CircularTypeAliasErrorType extends ErrorType
{

}
