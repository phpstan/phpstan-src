<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\TrinaryLogic;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;

interface BuiltinMethodReflection
{

	public function getName(): string;

	public function getReflection(): ?ReflectionMethod;

	public function getFileName(): ?string;

	public function getDeclaringClass(): ReflectionClass;

	public function getStartLine(): ?int;

	public function getEndLine(): ?int;

	public function getDocComment(): ?string;

	public function isStatic(): bool;

	public function isPrivate(): bool;

	public function isPublic(): bool;

	public function getPrototype(): self;

	public function isDeprecated(): TrinaryLogic;

	public function isVariadic(): bool;

	public function getReturnType(): ?ReflectionType;

	public function getTentativeReturnType(): ?ReflectionType;

	/**
	 * @return ReflectionParameter[]
	 */
	public function getParameters(): array;

	public function isFinal(): bool;

	public function isInternal(): bool;

	public function isAbstract(): bool;

}
