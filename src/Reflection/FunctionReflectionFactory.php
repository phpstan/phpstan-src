<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

interface FunctionReflectionFactory
{

	/**
	 * @param array<string, Type> $phpDocParameterTypes
	 * @param array<string, Type> $phpDocParameterOutTypes
	 * @param array<string, bool> $phpDocParameterImmediatelyInvokedCallable
	 * @param array<string, Type> $phpDocParameterClosureThisTypes
	 */
	public function create(
		ReflectionFunction $reflection,
		TemplateTypeMap $templateTypeMap,
		array $phpDocParameterTypes,
		?Type $phpDocReturnType,
		?Type $phpDocThrowType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
		bool $isFinal,
		?string $filename,
		?bool $isPure,
		Assertions $asserts,
		bool $acceptsNamedArguments,
		?string $phpDocComment,
		array $phpDocParameterOutTypes,
		array $phpDocParameterImmediatelyInvokedCallable,
		array $phpDocParameterClosureThisTypes,
	): PhpFunctionReflection;

}
