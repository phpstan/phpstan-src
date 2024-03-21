<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

interface FunctionReflectionFactory
{

	/**
	 * @param array<string, ParamTag> $phpDocParameterTags
	 * @param Type[] $phpDocParameterOutTypes
	 */
	public function create(
		ReflectionFunction $reflection,
		TemplateTypeMap $templateTypeMap,
		array $phpDocParameterTags,
		?Type $phpDocReturnType,
		?Type $phpDocThrowType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
		bool $isFinal,
		?string $filename,
		?bool $isPure,
		Assertions $asserts,
		?string $phpDocComment,
		array $phpDocParameterOutTypes,
	): PhpFunctionReflection;

}
