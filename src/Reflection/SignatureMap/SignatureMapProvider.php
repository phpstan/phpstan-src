<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use ReflectionFunctionAbstract;

interface SignatureMapProvider
{

	public function hasMethodSignature(string $className, string $methodName): bool;

	public function hasFunctionSignature(string $name): bool;

	/** @return array<int, FunctionSignature> */
	public function getMethodSignatures(string $className, string $methodName, ?ReflectionMethod $reflectionMethod): array;

	/** @return array<int, FunctionSignature> */
	public function getFunctionSignatures(string $functionName, ?string $className, ReflectionFunctionAbstract|null $reflectionFunction): array;

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
