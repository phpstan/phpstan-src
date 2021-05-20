<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

interface ExceptionTypeResolver
{

	public function isCheckedException(string $className): bool;

}
