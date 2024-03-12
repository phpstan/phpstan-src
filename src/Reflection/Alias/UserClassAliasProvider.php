<?php

namespace PHPStan\Reflection\Alias;

use PHPStan\Reflection\Alias\ClassAliasProvider;

class UserClassAliasProvider implements ClassAliasProvider
{

	/**
	 * @param array<class-string, class-string> $classAliasMap
	 */
	public function __construct(private array $classAliasMap)
	{
	}

    #[\Override] public function hasAlias(string $classAlias): bool
    {
        return array_key_exists($classAlias, $this->classAliasMap);
    }

    #[\Override] public function getClassName(string $classAlias): string
    {
        return $this->classAliasMap[$classAlias];
    }
}
