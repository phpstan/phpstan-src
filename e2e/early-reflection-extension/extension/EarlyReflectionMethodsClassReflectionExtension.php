<?php declare(strict_types = 1);

namespace EarlyReflectionExtension;

use Override;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;

final class EarlyReflectionMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		// Without a cache upgrade, this cleaned AST is reused during analysis and reports return.missing.
		$reflectionProvider->getClass(AnalysedClass::class)->getNativeReflection();
	}

	#[Override]
	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return false;
	}

	#[Override]
	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		throw new ShouldNotHappenException();
	}

}
