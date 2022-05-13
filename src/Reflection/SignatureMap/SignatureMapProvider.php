<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;

interface SignatureMapProvider
{

	public function hasMethodSignature(string $className, string $methodName): bool;

	public function hasFunctionSignature(string $name): bool;

	/** @return array<int, FunctionSignature> */
	public function getMethodSignatures(string $className, string $methodName, ?ReflectionMethod $reflectionMethod): array;

	/** @return array<int, FunctionSignature> */
	public function getFunctionSignatures(string $functionName, ?string $className, ReflectionFunction|ReflectionMethod|null $reflectionFunction): array;

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
