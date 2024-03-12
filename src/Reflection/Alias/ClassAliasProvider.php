<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Alias;

interface ClassAliasProvider
{
	public function hasAlias(string $classAlias): bool;

	public function getClassName(string $classAlias): string;

}
