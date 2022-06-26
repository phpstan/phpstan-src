<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPStan\Reflection\AllowedSubTypesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;

class DateTimeInterfaceAllowedSubTypesClassReflectionExtension implements AllowedSubTypesClassReflectionExtension
{

	public function supports(ClassReflection $classReflection): bool
	{
		return $classReflection->getName() === DateTimeInterface::class;
	}

	public function getAllowedSubTypes(ClassReflection $classReflection): array
	{
		return [
			new ObjectType(DateTime::class),
			new ObjectType(DateTimeImmutable::class),
		];
	}

}
