<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;

/** @api */
interface TypeWithClassName extends Type
{

	/** @return class-string */
	public function getClassName(): string;

	public function getAncestorWithClassName(string $className): ?self;

	public function getClassReflection(): ?ClassReflection;

}
