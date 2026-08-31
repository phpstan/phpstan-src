<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Closure;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use ReflectionClass as CoreReflectionClass;

interface ClassReflectionFactory
{

	/**
	 * The stub PHPDoc comes in as a callback because asking the StubPhpDocProvider for it parses
	 * every stub file, which runs the StubFilesExtensions. Those may rely on the bootstrapFiles,
	 * so the lookup must wait until someone reads ClassReflection::getResolvedPhpDoc().
	 *
	 * @param ReflectionClass|ReflectionEnum $reflection
	 * @param (Closure(): ?ResolvedPhpDocBlock)|null $stubPhpDocBlockCallback
	 */
	public function create(
		string $displayName,
		CoreReflectionClass $reflection,
		?string $anonymousFilename,
		?TemplateTypeMap $resolvedTemplateTypeMap,
		?Closure $stubPhpDocBlockCallback,
		?string $extraCacheKey = null,
		?TemplateTypeVarianceMap $resolvedCallSiteVarianceMap = null,
		?bool $finalByKeywordOverride = null,
	): ClassReflection;

}
