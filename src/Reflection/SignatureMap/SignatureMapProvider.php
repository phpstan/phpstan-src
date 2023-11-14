<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\Type\Type;
use ReflectionFunctionAbstract;

interface SignatureMapProvider
{

	public function hasMethodSignature(string $className, string $methodName): bool;

	public function hasFunctionSignature(string $name): bool;

	/** @return array{positional: array<int, FunctionSignature>, named: ?array<int, FunctionSignature>} */
	public function getMethodSignatures(string $className, string $methodName, ?ReflectionMethod $reflectionMethod): array;

	/** @return array{positional: array<int, FunctionSignature>, named: ?array<int, FunctionSignature>} */
	public function getFunctionSignatures(string $functionName, ?string $className, ?ReflectionFunctionAbstract $reflectionFunction): array;

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

	public function hasClassConstantMetadata(string $className, string $constantName): bool;

	/**
	 * @return array{nativeType: Type}
	 */
	public function getClassConstantMetadata(string $className, string $constantName): array;

}
