<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Analyser\Scope;

/** @api */
interface ExceptionTypeResolver
{

	public function isCheckedException(string $className, Scope $scope): bool;

}
