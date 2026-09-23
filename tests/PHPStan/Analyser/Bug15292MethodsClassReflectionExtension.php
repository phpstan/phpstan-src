<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\ShouldNotHappenException;

/**
 * Records the methods asked about, so a test can assert that a class is not reflected
 * for a callable-looking value that is never used as a callable.
 */
final class Bug15292MethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	/** @var list<string> */
	public static array $askedMethods = [];

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		self::$askedMethods[] = $classReflection->getName() . '::' . $methodName;

		return false;
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		throw new ShouldNotHappenException();
	}

}
