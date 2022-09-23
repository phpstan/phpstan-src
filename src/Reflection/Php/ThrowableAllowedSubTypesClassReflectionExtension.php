<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use Error;
use Exception;
use PHPStan\Reflection\AllowedSubTypesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Throwable;

class ThrowableAllowedSubTypesClassReflectionExtension implements AllowedSubTypesClassReflectionExtension
{

	public function supports(ClassReflection $classReflection): bool
	{
		return $classReflection->getName() === Throwable::class;
	}

	public function getAllowedSubTypes(ClassReflection $classReflection): array
	{
		return [
			new ObjectType(Error::class),
			new ObjectType(Exception::class), // phpcs:ignore SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
		];
	}

}
