<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

interface PhpMethodReflectionFactory
{

	/**
	 * @param Type[] $phpDocParameterTypes
	 * @param Type[] $phpDocParameterOutTypes
	 * @param array<string, TrinaryLogic> $immediatelyInvokedCallableParameters
	 * @param array<string, Type> $phpDocClosureThisTypeParameters
	 */
	public function create(
		ClassReflection $declaringClass,
		?ClassReflection $declaringTrait,
		BuiltinMethodReflection $reflection,
		TemplateTypeMap $templateTypeMap,
		array $phpDocParameterTypes,
		?Type $phpDocReturnType,
		?Type $phpDocThrowType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
		bool $isFinal,
		?bool $isPure,
		Assertions $asserts,
		bool $acceptsNamedArguments,
		?Type $selfOutType,
		?string $phpDocComment,
		array $phpDocParameterOutTypes,
		array $immediatelyInvokedCallableParameters = [],
		array $phpDocClosureThisTypeParameters = [],
	): PhpMethodReflection;

}
