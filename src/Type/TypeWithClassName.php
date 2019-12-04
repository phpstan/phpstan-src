<?php declare(strict_types = 1);

namespace PHPStan\Type;

interface TypeWithClassName extends Type
{

	/** @phpstan-return class-string */
	public function getClassName(): string;

	public function getAncestorWithClassName(string $className): ?ObjectType;

}
