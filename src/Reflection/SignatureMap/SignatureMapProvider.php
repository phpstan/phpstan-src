<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;

interface SignatureMapProvider
{

	public function hasMethodSignature(string $className, string $methodName, int $variant = 0): bool;

	public function hasFunctionSignature(string $name, int $variant = 0): bool;

	public function getMethodSignature(string $className, string $methodName, ?ReflectionMethod $reflectionMethod, int $variant = 0): FunctionSignature;

	public function getFunctionSignature(string $functionName, ?string $className, ReflectionFunction|ReflectionMethod|null $reflectionFunction, int $variant = 0): FunctionSignature;

	public function hasMethodMetadata(string $className, string $methodName): bool;

	public function hasFunctionMetadata(string $name): bool;

	/**
	 * @return array{hasSideEffects: bool}
	 */
	public function getMethodMetadata(string $className, string $methodName): array;

	/**
	 * @return array{hasSideEffects: bool}
	 */
	public function getFunctionMetadata(string $functionName): array;

}
