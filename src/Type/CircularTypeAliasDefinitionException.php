<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Exception;
use PHPStan\Turbo\ReferencedByTurboExtension;

#[ReferencedByTurboExtension(key: 'circularTypeAliasDefinitionException')]
final class CircularTypeAliasDefinitionException extends Exception
{

}
