<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType;
use PHPStan\TrinaryLogic;

interface BuiltinMethodReflection
{

	public function getName(): string;

	public function getReflection(): ReflectionMethod;

	public function getFileName(): ?string;

	public function getDeclaringClass(): ReflectionClass|ReflectionEnum;

	public function getStartLine(): ?int;

	public function getEndLine(): ?int;

	public function getDocComment(): ?string;

	public function isStatic(): bool;

	public function isPrivate(): bool;

	public function isPublic(): bool;

	public function getPrototype(): self;

	public function isDeprecated(): TrinaryLogic;

	public function isVariadic(): bool;

	public function getReturnType(): ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null;

	public function getTentativeReturnType(): ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null;

	/**
	 * @return ReflectionParameter[]
	 */
	public function getParameters(): array;

	public function isFinal(): bool;

	public function isInternal(): bool;

	public function isAbstract(): bool;

	public function returnsByReference(): TrinaryLogic;

}
