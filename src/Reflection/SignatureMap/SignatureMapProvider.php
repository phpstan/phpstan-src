<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

interface SignatureMapProvider
{

	public function hasFunctionSignature(string $name): bool;

	public function getFunctionSignature(string $functionName, ?string $className): FunctionSignature;

	public function hasFunctionMetadata(string $name): bool;

	/**
	 * @param string $functionName
	 * @return array{hasSideEffects: bool}
	 */
	public function getFunctionMetadata(string $functionName): array;

}
