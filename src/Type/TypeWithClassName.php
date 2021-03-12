<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;

interface TypeWithClassName extends Type
{

	public function getClassName(): string;

	public function getAncestorWithClassName(string $className): ?self;

	public function getClassReflection(): ?ClassReflection;

}
